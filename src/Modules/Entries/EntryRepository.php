<?php

declare(strict_types=1);

namespace App\Modules\Entries;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Database;
use App\Modules\Automation\CalcEngine;
use App\Modules\Clients\ClientRepository;

final class EntryRepository
{
    public static function payrollForClient(int $clientId): array
    {
        self::assertClient($clientId);
        return Database::fetchAll(
            'SELECT * FROM payroll_entries WHERE client_id = ? ORDER BY period_year DESC, period_month DESC',
            [$clientId]
        );
    }

    public static function salesForClient(int $clientId): array
    {
        self::assertClient($clientId);
        return Database::fetchAll(
            'SELECT * FROM sales_entries WHERE client_id = ? ORDER BY period_year DESC, period_month DESC',
            [$clientId]
        );
    }

    public static function savePayroll(int $clientId, array $data): int
    {
        self::assertClient($clientId);
        $existing = Database::fetchOne(
            'SELECT id FROM payroll_entries WHERE client_id = ? AND period_year = ? AND period_month = ?',
            [$clientId, $data['period_year'], $data['period_month']]
        );

        if ($existing) {
            Database::query(
                'UPDATE payroll_entries SET masse_salariale=?, effectif=?, entrees=?, sorties=?, nombre_assurees=?, source=?, notes=?, updated_at=NOW() WHERE id=?',
                [
                    $data['masse_salariale'], $data['effectif'], $data['entrees'], $data['sorties'],
                    $data['nombre_assurees'], $data['source'], $data['notes'], $existing['id'],
                ]
            );
            $id = (int) $existing['id'];
            AuditLog::write('update', 'payroll_entries', $id);
        } else {
            $id = Database::insert(
                'INSERT INTO payroll_entries (client_id, period_year, period_month, masse_salariale, effectif, entrees, sorties, nombre_assurees, source, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $clientId, $data['period_year'], $data['period_month'], $data['masse_salariale'],
                    $data['effectif'], $data['entrees'], $data['sorties'], $data['nombre_assurees'],
                    $data['source'], $data['notes'],
                ]
            );
            AuditLog::write('create', 'payroll_entries', $id);
        }

        CalcEngine::onPayrollEntrySaved($id);
        return $id;
    }

    public static function saveSales(int $clientId, array $data): int
    {
        self::assertClient($clientId);
        $existing = Database::fetchOne(
            'SELECT id FROM sales_entries WHERE client_id = ? AND period_year = ? AND (period_month <=> ?)',
            [$clientId, $data['period_year'], $data['period_month']]
        );

        if ($existing) {
            Database::query(
                'UPDATE sales_entries SET ca_biens=?, ca_services=?, ca_auto_entrepreneur=?, irg_acompte_base=?, source=?, notes=?, updated_at=NOW() WHERE id=?',
                [
                    $data['ca_biens'], $data['ca_services'], $data['ca_auto_entrepreneur'],
                    $data['irg_acompte_base'], $data['source'], $data['notes'], $existing['id'],
                ]
            );
            $id = (int) $existing['id'];
            AuditLog::write('update', 'sales_entries', $id);
        } else {
            $id = Database::insert(
                'INSERT INTO sales_entries (client_id, period_year, period_month, ca_biens, ca_services, ca_auto_entrepreneur, irg_acompte_base, source, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $clientId, $data['period_year'], $data['period_month'], $data['ca_biens'],
                    $data['ca_services'], $data['ca_auto_entrepreneur'], $data['irg_acompte_base'],
                    $data['source'], $data['notes'],
                ]
            );
            AuditLog::write('create', 'sales_entries', $id);
        }

        CalcEngine::onSalesEntrySaved($id);
        return $id;
    }

    public static function createFromOcr(int $clientId, array $extracted): ?int
    {
        if (($extracted['entry_type'] ?? '') === 'payroll') {
            return self::savePayroll($clientId, [
                'period_year' => (int) ($extracted['period_year'] ?? date('Y')),
                'period_month' => (int) ($extracted['period_month'] ?? date('n')),
                'masse_salariale' => (float) ($extracted['masse_salariale'] ?? $extracted['salaire_base'] ?? 0),
                'effectif' => (int) ($extracted['effectif'] ?? 1),
                'entrees' => (int) ($extracted['entrees'] ?? 0),
                'sorties' => (int) ($extracted['sorties'] ?? 0),
                'nombre_assurees' => (int) ($extracted['nombre_assurees'] ?? $extracted['effectif'] ?? 1),
                'source' => 'ocr',
                'notes' => 'Import OCR — ' . ($extracted['employee_name'] ?? ''),
            ]);
        }
        if (($extracted['entry_type'] ?? '') === 'sales') {
            return self::saveSales($clientId, [
                'period_year' => (int) ($extracted['period_year'] ?? date('Y')),
                'period_month' => isset($extracted['period_month']) ? (int) $extracted['period_month'] : null,
                'ca_biens' => (float) ($extracted['ca_biens'] ?? 0),
                'ca_services' => (float) ($extracted['ca_services'] ?? 0),
                'ca_auto_entrepreneur' => (float) ($extracted['ca_auto_entrepreneur'] ?? 0),
                'irg_acompte_base' => isset($extracted['irg_acompte_base']) ? (float) $extracted['irg_acompte_base'] : null,
                'source' => 'ocr',
                'notes' => 'Import OCR',
            ]);
        }
        return null;
    }

    private static function assertClient(int $clientId): void
    {
        $row = Database::fetchOne('SELECT id FROM clients WHERE id = ?', [$clientId]);
        if (!$row) {
            throw new \RuntimeException('Client not found');
        }
        if (Auth::check() && !ClientRepository::find($clientId)) {
            throw new \RuntimeException('Client not found');
        }
    }
}
