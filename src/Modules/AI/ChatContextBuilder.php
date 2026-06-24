<?php

declare(strict_types=1);

namespace App\Modules\AI;

use App\Core\Auth;
use App\Core\Database;
use App\Modules\Automation\DeadlineService;

final class ChatContextBuilder
{
    public static function build(?int $clientId = null): string
    {
        $cabinetId = Auth::cabinetId();
        $parts = ["Tu es l'assistant IA du cabinet comptable algérien. Réponds en français, de façon précise et professionnelle. Tu connais CNAS, CACOBATPH, G50, G12, IRG, IFU."];

        if ($clientId) {
            $client = Database::fetchOne('SELECT * FROM clients WHERE id = ? AND cabinet_id = ?', [$clientId, $cabinetId]);
            if ($client) {
                $parts[] = "\n## Client actuel: {$client['raison_sociale']}";
                $parts[] = "Secteur: {$client['secteur']}, Régime CNAS: {$client['cnas_regime']}, Wilaya: {$client['wilaya']}";
                foreach (DeadlineService::clientObligations((int) $client['id']) as $ob) {
                    if (in_array($ob['status'], ['overdue', 'missing_data', 'draft_ready'], true)) {
                        $parts[] = "- {$ob['type_label']} ({$ob['period_label']}): {$ob['status_label']}, échéance {$ob['due_label']}"
                            . ($ob['amount'] ? ', montant ' . number_format($ob['amount'], 2, ',', ' ') . ' DA' : '');
                    }
                }
                $decls = Database::fetchAll(
                    'SELECT type, status, computed_fields, period_year, period_month FROM declarations WHERE client_id = ? ORDER BY created_at DESC LIMIT 8',
                    [$clientId]
                );
                foreach ($decls as $d) {
                    $cf = json_decode($d['computed_fields'], true);
                    $parts[] = "Déclaration {$d['type']} {$d['status']}: total " . number_format((float) ($cf['total'] ?? 0), 2, ',', ' ') . ' DA';
                }
                $docs = Database::fetchAll(
                    "SELECT title, category, ged_status, doc_type FROM documents WHERE client_id = ? ORDER BY created_at DESC LIMIT 10",
                    [$clientId]
                );
                if ($docs) {
                    $parts[] = "\nDocuments récents dans le dossier:";
                    foreach ($docs as $doc) {
                        $parts[] = "- [{$doc['category']}] {$doc['title']} ({$doc['ged_status']})";
                    }
                }
            }
        } else {
            $stats = DeadlineService::cabinetStats($cabinetId);
            $parts[] = "\n## Vue cabinet";
            $parts[] = "Retards: {$stats['overdue_count']}, Données manquantes: {$stats['missing_data_count']}, Brouillons prêts: {$stats['draft_ready_count']}";
            $parts[] = 'Montants à traiter (30j): ' . number_format($stats['total_amount_due'], 0, ',', ' ') . ' DA';
            foreach (DeadlineService::clientsWithStatus($cabinetId) as $c) {
                if ($c['overdue'] || $c['missing']) {
                    $parts[] = "- {$c['raison_sociale']}: {$c['overdue']} retard(s), {$c['missing']} donnée(s) manquante(s), conformité {$c['compliance']}%";
                }
            }
        }

        return implode("\n", $parts);
    }
}
