<?php

declare(strict_types=1);

namespace App\Modules\Declarations;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Database;

final class DeclarationRepository
{
    public static function allForCabinet(?string $status = null): array
    {
        $sql = 'SELECT d.*, c.raison_sociale FROM declarations d
                JOIN clients c ON c.id = d.client_id
                WHERE c.cabinet_id = ?';
        $params = [Auth::cabinetId()];
        if ($status) {
            $sql .= ' AND d.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY d.created_at DESC';
        $rows = Database::fetchAll($sql, $params);
        return array_map([self::class, 'decode'], $rows);
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetchOne(
            'SELECT d.*, c.raison_sociale, c.secteur, c.numero_cotisant, c.nif_encrypted, c.wilaya, c.adresse, c.activite
             FROM declarations d JOIN clients c ON c.id = d.client_id
             WHERE d.id = ? AND c.cabinet_id = ?',
            [$id, Auth::cabinetId()]
        );
        return $row ? self::decode($row) : null;
    }

    public static function approve(int $id): void
    {
        Database::query(
            'UPDATE declarations d JOIN clients c ON c.id = d.client_id
             SET d.status = ?, d.reviewed_by = ?, d.reviewed_at = NOW(), d.updated_at = NOW()
             WHERE d.id = ? AND c.cabinet_id = ? AND d.status = ?',
            ['APPROVED', Auth::id(), $id, Auth::cabinetId(), 'DRAFT_CALCULATED']
        );
        AuditLog::write('approve', 'declarations', $id);
    }

    /** @return array{approved: int, skipped: int} */
    public static function approveBatch(array $ids): array
    {
        $approved = 0;
        $skipped = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $before = Database::fetchOne(
                "SELECT d.status FROM declarations d JOIN clients c ON c.id = d.client_id
                 WHERE d.id = ? AND c.cabinet_id = ?",
                [$id, Auth::cabinetId()]
            );
            if (!$before || $before['status'] !== 'DRAFT_CALCULATED') {
                $skipped++;
                continue;
            }
            self::approve($id);
            $approved++;
        }
        return compact('approved', 'skipped');
    }

    public static function submit(int $id, ?array $checklist = null, ?string $receiptPath = null): void
    {
        Database::query(
            'UPDATE declarations d JOIN clients c ON c.id = d.client_id
             SET d.status = ?, d.submitted_at = NOW(), d.updated_at = NOW(),
                 d.checklist_json = ?, d.receipt_path = COALESCE(?, d.receipt_path)
             WHERE d.id = ? AND c.cabinet_id = ? AND d.status = ?',
            [
                'SUBMITTED',
                $checklist ? json_encode($checklist) : null,
                $receiptPath,
                $id,
                Auth::cabinetId(),
                'APPROVED',
            ]
        );
        AuditLog::write('submit', 'declarations', $id, $checklist);
    }

    public static function updateComputed(int $id, array $computed): void
    {
        Database::query(
            'UPDATE declarations d JOIN clients c ON c.id = d.client_id
             SET d.computed_fields = ?, d.updated_at = NOW()
             WHERE d.id = ? AND c.cabinet_id = ? AND d.status = ?',
            [json_encode($computed, JSON_UNESCAPED_UNICODE), $id, Auth::cabinetId(), 'DRAFT_CALCULATED']
        );
        AuditLog::write('update_computed', 'declarations', $id);
    }

    public static function deleteDraft(int $id): bool
    {
        $row = Database::fetchOne(
            "SELECT d.id FROM declarations d JOIN clients c ON c.id = d.client_id
             WHERE d.id = ? AND c.cabinet_id = ? AND d.status = 'DRAFT_CALCULATED'",
            [$id, Auth::cabinetId()]
        );
        if (!$row) {
            return false;
        }
        Database::query('DELETE FROM declarations WHERE id = ?', [$id]);
        AuditLog::write('delete', 'declarations', $id);

        return true;
    }

    /** @param list<int> $ids */
    public static function bulkDeleteDrafts(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if (self::deleteDraft((int) $id)) {
                $count++;
            }
        }

        return $count;
    }

    public static function stats(): array
    {
        $cabinetId = Auth::cabinetId();
        return [
            'drafts' => (int) Database::fetchOne(
                "SELECT COUNT(*) AS c FROM declarations d JOIN clients c ON c.id = d.client_id
                 WHERE c.cabinet_id = ? AND d.status = 'DRAFT_CALCULATED'",
                [$cabinetId]
            )['c'],
            'approved' => (int) Database::fetchOne(
                "SELECT COUNT(*) AS c FROM declarations d JOIN clients c ON c.id = d.client_id
                 WHERE c.cabinet_id = ? AND d.status = 'APPROVED'",
                [$cabinetId]
            )['c'],
            'clients' => (int) Database::fetchOne(
                'SELECT COUNT(*) AS c FROM clients WHERE cabinet_id = ? AND is_active = 1',
                [$cabinetId]
            )['c'],
        ];
    }

    public static function sourceData(int $declarationId): ?array
    {
        $decl = self::find($declarationId);
        if (!$decl) {
            return null;
        }
        if ($decl['payroll_entry_id']) {
            return Database::fetchOne('SELECT * FROM payroll_entries WHERE id = ?', [$decl['payroll_entry_id']]);
        }
        if ($decl['sales_entry_id']) {
            return Database::fetchOne('SELECT * FROM sales_entries WHERE id = ?', [$decl['sales_entry_id']]);
        }
        return null;
    }

    public static function previousPeriod(array $declaration): ?array
    {
        $year = (int) $declaration['period_year'];
        $month = $declaration['period_month'] ? (int) $declaration['period_month'] : null;
        $quarter = $declaration['period_quarter'] ? (int) $declaration['period_quarter'] : null;

        if ($month) {
            $prevMonth = $month - 1;
            $prevYear = $year;
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear--;
            }
            $row = Database::fetchOne(
                'SELECT * FROM declarations WHERE client_id = ? AND type = ? AND period_year = ? AND period_month = ? AND id != ? ORDER BY id DESC LIMIT 1',
                [$declaration['client_id'], $declaration['type'], $prevYear, $prevMonth, $declaration['id']]
            );
        } elseif ($quarter) {
            $prevQ = $quarter - 1;
            $prevYear = $year;
            if ($prevQ < 1) {
                $prevQ = 4;
                $prevYear--;
            }
            $row = Database::fetchOne(
                'SELECT * FROM declarations WHERE client_id = ? AND type = ? AND period_year = ? AND period_quarter = ? AND id != ? ORDER BY id DESC LIMIT 1',
                [$declaration['client_id'], $declaration['type'], $prevYear, $prevQ, $declaration['id']]
            );
        } else {
            return null;
        }
        return $row ? self::decode($row) : null;
    }

    private static function decode(array $row): array
    {
        $row['computed_fields'] = json_decode($row['computed_fields'], true) ?: [];
        $row['nif'] = \App\Core\Encryption::decrypt($row['nif_encrypted'] ?? null);
        return $row;
    }
}
