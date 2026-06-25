<?php

declare(strict_types=1);

namespace App\Modules\Logs;

use App\Core\Database;
use App\Modules\Automation\AutomationPipeline;

final class ActivityLogService
{
    /** @return list<array<string, mixed>> */
    public static function auditLogs(int $cabinetId, int $limit = 150): array
    {
        $limit = max(1, min(500, $limit));

        return Database::fetchAll(
            "SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.cabinet_id = ?
             ORDER BY a.created_at DESC
             LIMIT {$limit}",
            [$cabinetId]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function automationRuns(int $cabinetId, int $limit = 50): array
    {
        return AutomationPipeline::recentRuns($cabinetId, max(1, min(100, $limit)));
    }

    /** @return list<array<string, mixed>> */
    public static function jobQueue(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));

        return Database::fetchAll(
            "SELECT id, type, status, attempts, error_message, created_at, updated_at
             FROM job_queue
             ORDER BY created_at DESC
             LIMIT {$limit}"
        );
    }

    /** @return array{audit: int, automation: int, jobs_pending: int, jobs_failed: int} */
    public static function counts(int $cabinetId): array
    {
        $audit = Database::fetchOne('SELECT COUNT(*) AS c FROM audit_logs WHERE cabinet_id = ?', [$cabinetId]);
        $auto = Database::fetchOne('SELECT COUNT(*) AS c FROM automation_runs WHERE cabinet_id = ?', [$cabinetId]);
        $pending = Database::fetchOne("SELECT COUNT(*) AS c FROM job_queue WHERE status IN ('pending','processing')");
        $failed = Database::fetchOne("SELECT COUNT(*) AS c FROM job_queue WHERE status = 'failed'");

        return [
            'audit' => (int) ($audit['c'] ?? 0),
            'automation' => (int) ($auto['c'] ?? 0),
            'jobs_pending' => (int) ($pending['c'] ?? 0),
            'jobs_failed' => (int) ($failed['c'] ?? 0),
        ];
    }

    public static function entityLink(string $entity, ?int $entityId): ?string
    {
        if ($entityId === null || $entityId <= 0) {
            return null;
        }

        return match ($entity) {
            'clients' => '/clients/' . $entityId,
            'declarations' => '/declarations/' . $entityId,
            'payroll_entries', 'sales_entries' => '/declarations',
            'documents' => '/documents/' . $entityId,
            default => null,
        };
    }
}
