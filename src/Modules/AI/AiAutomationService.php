<?php

declare(strict_types=1);

namespace App\Modules\AI;

use App\Core\Auth;
use App\Core\Database;
use App\Modules\Automation\DeadlineService;
use App\Modules\Declarations\DeclarationRepository;

final class AiAutomationService
{
    /** @return array{summary: string, risks: list<string>, actions: list<string>}|null */
    public static function reviewDeclaration(int $declarationId): ?array
    {
        $declaration = DeclarationRepository::find($declarationId);
        if (!$declaration) {
            return null;
        }

        $source = DeclarationRepository::sourceData($declarationId);
        $previous = DeclarationRepository::previousPeriod($declaration);
        $cf = $declaration['computed_fields'];

        $prompt = "Analyse cette déclaration comptable algérienne et réponds UNIQUEMENT en JSON valide:\n"
            . '{"summary":"2-3 phrases","risks":["..."],"actions":["..."]}\n\n'
            . 'Type: ' . $declaration['type'] . "\n"
            . 'Client: ' . $declaration['raison_sociale'] . "\n"
            . 'Statut: ' . $declaration['status'] . "\n"
            . 'Total: ' . ($cf['total'] ?? 0) . " DA\n"
            . 'Lignes: ' . json_encode($cf['lines'] ?? [], JSON_UNESCAPED_UNICODE) . "\n"
            . 'Source: ' . json_encode($source ?: [], JSON_UNESCAPED_UNICODE) . "\n"
            . 'Période précédente total: ' . ($previous ? ($previous['computed_fields']['total'] ?? 'N/A') : 'N/A');

        $parsed = OpenRouterClient::jsonPrompt($prompt, 'Tu es expert-comptable agréé en Algérie. JSON strict, français.');
        if (!$parsed) {
            return null;
        }

        $review = [
            'summary' => (string) ($parsed['summary'] ?? ''),
            'risks' => array_values(array_filter((array) ($parsed['risks'] ?? []))),
            'actions' => array_values(array_filter((array) ($parsed['actions'] ?? []))),
            'generated_at' => date('c'),
        ];

        Database::query(
            'UPDATE declarations d JOIN clients c ON c.id = d.client_id
             SET d.ai_review_json = ? WHERE d.id = ? AND c.cabinet_id = ?',
            [json_encode($review, JSON_UNESCAPED_UNICODE), $declarationId, Auth::cabinetId()]
        );

        return $review;
    }

    /** @return array{category: string, title: string, doc_type: string}|null */
    public static function classifyDocument(string $filename, string $text, ?string $clientSecteur = null): ?array
    {
        $categories = 'paie, social, fiscal, factures, banque, juridique, divers';
        $prompt = "Classifie ce document pour un dossier client comptable algérien.\n"
            . "Fichier: {$filename}\n"
            . ($clientSecteur ? "Secteur client: {$clientSecteur}\n" : '')
            . "Texte (extrait):\n" . mb_substr($text, 0, 4000) . "\n\n"
            . 'Réponds JSON uniquement: {"category":"' . $categories . '","title":"titre court","doc_type":"cnas|cacobatph|g50|g12|fiche_paie|facture|autre"}';

        $parsed = OpenRouterClient::jsonPrompt($prompt, 'Classification GED cabinet comptable. JSON only.');
        if (!$parsed || empty($parsed['category'])) {
            return null;
        }

        $allowed = ['paie', 'social', 'fiscal', 'factures', 'banque', 'juridique', 'divers'];
        if (!in_array($parsed['category'], $allowed, true)) {
            $parsed['category'] = 'divers';
        }

        return [
            'category' => $parsed['category'],
            'title' => (string) ($parsed['title'] ?? $filename),
            'doc_type' => (string) ($parsed['doc_type'] ?? 'autre'),
        ];
    }

    public static function generateRelanceMessage(int $clientId, string $obligationLabel, string $statusLabel): ?string
    {
        $client = Database::fetchOne(
            'SELECT raison_sociale, secteur, wilaya FROM clients WHERE id = ? AND cabinet_id = ?',
            [$clientId, Auth::cabinetId()]
        );
        if (!$client) {
            return null;
        }

        $messages = [
            ['role' => 'system', 'content' => 'Tu rédiges des relances professionnelles courtes en français pour un cabinet comptable algérien. Ton poli, factuel, 3-5 phrases max.'],
            ['role' => 'user', 'content' => sprintf(
                "Rédige un message de relance pour le client %s (%s) concernant: %s. Situation: %s. Demander les pièces manquantes ou confirmer le dépôt.",
                $client['raison_sociale'],
                $client['secteur'],
                $obligationLabel,
                $statusLabel
            )],
        ];

        return OpenRouterClient::chat($messages);
    }

    /** @return array{reviewed: int, classified: int, errors: list<string>} */
    public static function batchReviewDrafts(int $cabinetId, int $limit = 5): array
    {
        $drafts = Database::fetchAll(
            "SELECT d.id FROM declarations d JOIN clients c ON c.id = d.client_id
             WHERE c.cabinet_id = ? AND d.status = 'DRAFT_CALCULATED' AND d.ai_review_json IS NULL
             ORDER BY d.created_at DESC LIMIT " . max(1, min(20, $limit)),
            [$cabinetId]
        );

        $reviewed = 0;
        $errors = [];
        foreach ($drafts as $d) {
            try {
                if (self::reviewDeclaration((int) $d['id'])) {
                    $reviewed++;
                }
            } catch (\Throwable $e) {
                $errors[] = 'Decl #' . $d['id'] . ': ' . $e->getMessage();
            }
        }

        return ['reviewed' => $reviewed, 'classified' => 0, 'errors' => $errors];
    }
}
