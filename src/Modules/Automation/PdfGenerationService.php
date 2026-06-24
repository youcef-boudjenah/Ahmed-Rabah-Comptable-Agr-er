<?php

declare(strict_types=1);

namespace App\Modules\Automation;

use App\Core\Auth;
use App\Core\Database;
use App\Modules\Declarations\DeclarationRepository;
use App\Modules\Automation\WorkflowService;

final class PdfGenerationService
{
    public static function generate(int $declarationId): ?string
    {
        $declaration = DeclarationRepository::find($declarationId);
        if (!$declaration) {
            return null;
        }

        $periodLabel = DeadlineService::periodLabel($declaration['type'], [
            'year' => (int) $declaration['period_year'],
            'month' => $declaration['period_month'] ? (int) $declaration['period_month'] : null,
            'quarter' => $declaration['period_quarter'] ? (int) $declaration['period_quarter'] : null,
        ]);

        $path = BordereauRenderer::generateFile($declarationId);
        if ($path === null) {
            return null;
        }

        Database::query(
            'UPDATE declarations d JOIN clients c ON c.id = d.client_id
             SET d.generated_pdf_path = ?, d.updated_at = NOW()
             WHERE d.id = ? AND c.cabinet_id = ?',
            [$path, $declarationId, Auth::cabinetId()]
        );

        WorkflowService::onBordereauGenerated($declarationId, $path);

        return $path;
    }

    /** @return array{generated: int, skipped: int, items: list<array<string, mixed>>} */
    public static function generateAllDraftsAndApproved(int $cabinetId): array
    {
        $rows = Database::fetchAll(
            "SELECT d.id, d.type, c.raison_sociale FROM declarations d JOIN clients c ON c.id = d.client_id
             WHERE c.cabinet_id = ? AND d.status IN ('DRAFT_CALCULATED', 'APPROVED')
             AND (d.generated_pdf_path IS NULL OR d.generated_pdf_path = '')",
            [$cabinetId]
        );

        $items = [];
        $generated = 0;
        foreach ($rows as $row) {
            $path = self::generate((int) $row['id']);
            $label = $row['type'] . ' — ' . $row['raison_sociale'];
            if ($path) {
                $generated++;
                $items[] = ['id' => $row['id'], 'label' => $label, 'status' => 'ok', 'path' => $path];
            } else {
                $items[] = ['id' => $row['id'], 'label' => $label, 'status' => 'failed'];
            }
        }

        return ['generated' => $generated, 'skipped' => count($rows) - $generated, 'items' => $items];
    }
}
