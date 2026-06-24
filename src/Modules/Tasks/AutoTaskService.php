<?php

declare(strict_types=1);

namespace App\Modules\Tasks;

use App\Core\Database;

final class AutoTaskService
{
    /** @return array{created: int, skipped: int} */
    public static function syncFromObligations(int $cabinetId): array
    {
        $upcoming = \App\Modules\Automation\DeadlineService::cabinetUpcoming($cabinetId, 45);
        $created = 0;
        $skipped = 0;

        foreach ($upcoming as $ob) {
            if (!in_array($ob['status'], ['missing_data', 'overdue', 'draft_ready'], true)) {
                continue;
            }

            $title = match ($ob['status']) {
                'missing_data' => 'Relancer — données ' . $ob['type_label'] . ' (' . $ob['period_label'] . ')',
                'overdue' => 'URGENT — ' . $ob['type_label'] . ' en retard (' . $ob['period_label'] . ')',
                default => 'Valider brouillon — ' . $ob['type_label'] . ' (' . $ob['period_label'] . ')',
            };

            $exists = Database::fetchOne(
                'SELECT id FROM cabinet_tasks WHERE cabinet_id = ? AND client_id = ? AND is_done = 0 AND title = ?',
                [$cabinetId, $ob['client_id'], $title]
            );
            if ($exists) {
                $skipped++;
                continue;
            }

            $priority = $ob['status'] === 'overdue' ? 'high' : ($ob['status'] === 'draft_ready' ? 'normal' : 'high');
            $due = $ob['due_date']->format('Y-m-d');

            $userId = (int) (Database::fetchOne('SELECT id FROM users WHERE cabinet_id = ? ORDER BY id LIMIT 1', [$cabinetId])['id'] ?? 1);

            TaskRepository::create($cabinetId, $userId, $title, (int) $ob['client_id'], $due, $priority);
            $created++;
        }

        return compact('created', 'skipped');
    }
}
