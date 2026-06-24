<?php

declare(strict_types=1);

namespace App\Modules\Automation;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Queue;
use App\Modules\Admin\SettingsService;
use App\Modules\Documents\ClientFolderService;
use App\Modules\Documents\DocumentRepository;
use App\Modules\Documents\OcrService;

/**
 * Relie saisie → déclaration → PDF → GED en un flux cohérent.
 */
final class WorkflowService
{
    /** URL de saisie selon le type d'obligation */
    public static function entryUrlForType(string $obligationType, int $clientId): string
    {
        $q = '?client=' . $clientId;
        if (in_array($obligationType, ['G50', 'G12', 'G12_BIS'], true)) {
            return '/entries/sales' . $q;
        }
        return '/entries/payroll' . $q;
    }

    /** Catégorie GED pour une déclaration */
    public static function gedCategoryForDeclaration(string $type): string
    {
        return match (true) {
            str_starts_with($type, 'CNAS'), $type === 'CACOBATPH' => 'social',
            in_array($type, ['G50', 'G12', 'G12_BIS'], true) => 'fiscal',
            default => 'social',
        };
    }

    /** Dernière déclaration brouillon créée/mise à jour pour un client */
    public static function latestDraftId(int $clientId, ?array $types = null): ?int
    {
        $sql = "SELECT d.id FROM declarations d
                JOIN clients c ON c.id = d.client_id
                WHERE d.client_id = ? AND c.cabinet_id = ? AND d.status = 'DRAFT_CALCULATED'";
        $params = [$clientId, Auth::cabinetId()];
        if ($types) {
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $sql .= " AND d.type IN ($placeholders)";
            $params = array_merge($params, $types);
        }
        $sql .= ' ORDER BY d.updated_at DESC, d.id DESC LIMIT 1';
        $row = Database::fetchOne($sql, $params);
        return $row ? (int) $row['id'] : null;
    }

    /** Après saisie paie — déclaration CNAS principale */
    public static function afterPayrollSaved(int $clientId): ?int
    {
        return self::latestDraftId($clientId, ['CNAS_MENSUELLE', 'CNAS_TRIMESTRIELLE', 'CACOBATPH']);
    }

    /** Après saisie ventes — déclaration G50/G12 */
    public static function afterSalesSaved(int $clientId): ?int
    {
        return self::latestDraftId($clientId, ['G50', 'G12', 'G12_BIS']);
    }

    /**
     * Prochaine action recommandée dans le workflow déclaration.
     *
     * @return array{step: int, total: int, label: string, description: string, action_label: string, action_url: string, action_method: string}|null
     */
    public static function nextStep(array $declaration): ?array
    {
        $id = (int) $declaration['id'];
        $status = $declaration['status'];
        $clientId = (int) $declaration['client_id'];
        $hasPdf = !empty($declaration['generated_pdf_path']) && is_file($declaration['generated_pdf_path']);

        if ($status === 'DRAFT_CALCULATED') {
            return [
                'step' => 2,
                'total' => 5,
                'label' => 'Vérifier et approuver',
                'description' => 'Contrôlez les montants calculés, puis approuvez pour générer le bordereau.',
                'action_label' => 'Approuver la déclaration',
                'action_url' => '/declarations/' . $id,
                'action_method' => 'form_approve',
            ];
        }
        if ($status === 'APPROVED') {
            if (!$hasPdf) {
                return [
                    'step' => 3,
                    'total' => 5,
                    'label' => 'Générer le bordereau',
                    'description' => 'Le PDF imprimable n\'existe pas encore.',
                    'action_label' => 'Générer bordereau',
                    'action_url' => '/declarations/' . $id . '/generate-pdf',
                    'action_method' => 'post',
                ];
            }
            return [
                'step' => 4,
                'total' => 5,
                'label' => 'Déposer et archiver',
                'description' => 'Imprimez, déposez auprès de l\'organisme, puis joignez la quittance.',
                'action_label' => 'Formulaire de dépôt',
                'action_url' => '/declarations/' . $id . '#depot',
                'action_method' => 'anchor',
            ];
        }
        if ($status === 'SUBMITTED') {
            return [
                'step' => 5,
                'total' => 5,
                'label' => 'Dossier archivé',
                'description' => 'Consultez les pièces dans le dossier GED du client.',
                'action_label' => 'Ouvrir dossier GED',
                'action_url' => '/clients/' . $clientId . '/dossier?cat=' . self::gedCategoryForDeclaration($declaration['type']),
                'action_method' => 'get',
            ];
        }
        return null;
    }

    /** Étapes du workflow client (vue d'ensemble) */
    public static function clientPipeline(int $clientId): array
    {
        $obligations = DeadlineService::clientObligations($clientId);
        $urgent = array_values(array_filter($obligations, fn ($o) => in_array($o['status'], ['overdue', 'missing_data', 'draft_ready'], true)));
        return [
            'urgent_count' => count($urgent),
            'urgent' => array_slice($urgent, 0, 5),
            'compliance' => DeadlineService::complianceScore($clientId),
        ];
    }

    /** Archive un fichier existant dans le GED client (copie, pas upload HTTP) */
    public static function archiveToGed(
        int $clientId,
        string $sourcePath,
        string $category,
        string $title,
        ?int $declarationId = null,
        string $docType = 'autre'
    ): ?int {
        if (!is_file($sourcePath)) {
            return null;
        }

        $existing = Database::fetchOne(
            'SELECT id FROM documents WHERE client_id = ? AND declaration_id = ? AND title = ? LIMIT 1',
            [$clientId, $declarationId, $title]
        );
        if ($existing) {
            return (int) $existing['id'];
        }

        ClientFolderService::ensure($clientId);
        $dir = ClientFolderService::categoryPath($clientId, $category);
        $base = basename($sourcePath);
        $dest = $dir . '/' . date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $base);

        if (!copy($sourcePath, $dest)) {
            return null;
        }

        $mime = mime_content_type($dest) ?: 'application/octet-stream';
        $id = Database::insert(
            'INSERT INTO documents (cabinet_id, client_id, declaration_id, original_name, title, file_path, mime, file_size, category, subfolder, doc_type, status, ged_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                Auth::cabinetId(),
                $clientId,
                $declarationId,
                $base,
                $title,
                $dest,
                $mime,
                (int) filesize($dest),
                $category,
                $category,
                $docType,
                'done',
                'traite',
            ]
        );
        return $id;
    }

    /** Après génération bordereau → copie dans GED */
    public static function onBordereauGenerated(int $declarationId, string $filePath): void
    {
        $decl = Database::fetchOne(
            'SELECT d.*, c.cabinet_id FROM declarations d JOIN clients c ON c.id = d.client_id WHERE d.id = ? AND c.cabinet_id = ?',
            [$declarationId, Auth::cabinetId()]
        );
        if (!$decl) {
            return;
        }

        $typeLabel = DeadlineService::typeLabel($decl['type']);
        $periodLabel = DeadlineService::periodLabel($decl['type'], [
            'year' => (int) $decl['period_year'],
            'month' => $decl['period_month'] ? (int) $decl['period_month'] : null,
            'quarter' => $decl['period_quarter'] ? (int) $decl['period_quarter'] : null,
        ]);
        $title = "Bordereau {$typeLabel} — {$periodLabel}";

        self::archiveToGed(
            (int) $decl['client_id'],
            $filePath,
            self::gedCategoryForDeclaration($decl['type']),
            $title,
            $declarationId,
            strtolower($decl['type'])
        );
    }

    /** Après dépôt avec quittance → archive dans GED */
    public static function onReceiptUploaded(int $declarationId, string $receiptPath): void
    {
        $decl = Database::fetchOne(
            'SELECT d.* FROM declarations d JOIN clients c ON c.id = d.client_id WHERE d.id = ? AND c.cabinet_id = ?',
            [$declarationId, Auth::cabinetId()]
        );
        if (!$decl) {
            return;
        }

        $typeLabel = DeadlineService::typeLabel($decl['type']);
        $periodLabel = DeadlineService::periodLabel($decl['type'], [
            'year' => (int) $decl['period_year'],
            'month' => $decl['period_month'] ? (int) $decl['period_month'] : null,
            'quarter' => $decl['period_quarter'] ? (int) $decl['period_quarter'] : null,
        ]);

        self::archiveToGed(
            (int) $decl['client_id'],
            $receiptPath,
            self::gedCategoryForDeclaration($decl['type']),
            "Quittance {$typeLabel} — {$periodLabel}",
            $declarationId,
            'quittance'
        );
    }

    /** Documents GED liés à une déclaration */
    public static function documentsForDeclaration(int $declarationId): array
    {
        return Database::fetchAll(
            'SELECT d.* FROM documents d
             JOIN clients c ON c.id = d.client_id
             WHERE d.declaration_id = ? AND c.cabinet_id = ?
             ORDER BY d.created_at DESC',
            [$declarationId, Auth::cabinetId()]
        );
    }

    public static function shouldProcessOcrOnUpload(): bool
    {
        return SettingsService::bool('auto_ocr_on_upload');
    }

    public static function queueOcrIfEnabled(int $documentId): void
    {
        if (self::shouldProcessOcrOnUpload()) {
            Queue::push('OCR_EXTRACT', ['document_id' => $documentId]);
        }
    }
}
