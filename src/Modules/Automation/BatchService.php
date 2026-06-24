<?php

declare(strict_types=1);

namespace App\Modules\Automation;

use App\Core\Database;
use App\Modules\Alerts\AlertService;

final class BatchService
{
    /** @return array{payroll: int, sales: int, alerts: int} */
    public static function recalculateCabinet(int $cabinetId): array
    {
        $payrollIds = Database::fetchAll(
            'SELECT pe.id FROM payroll_entries pe JOIN clients c ON c.id = pe.client_id WHERE c.cabinet_id = ?',
            [$cabinetId]
        );
        foreach ($payrollIds as $row) {
            CalcEngine::onPayrollEntrySaved((int) $row['id']);
        }

        $salesIds = Database::fetchAll(
            'SELECT se.id FROM sales_entries se JOIN clients c ON c.id = se.client_id WHERE c.cabinet_id = ?',
            [$cabinetId]
        );
        foreach ($salesIds as $row) {
            CalcEngine::onSalesEntrySaved((int) $row['id']);
        }

        AlertService::syncAllForCabinet($cabinetId);

        return [
            'payroll' => count($payrollIds),
            'sales' => count($salesIds),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function clientsToChase(int $cabinetId, int $limit = 10): array
    {
        $items = [];
        foreach (DeadlineService::cabinetUpcoming($cabinetId, 60) as $ob) {
            if (!in_array($ob['status'], ['missing_data', 'overdue'], true)) {
                continue;
            }
            $client = Database::fetchOne(
                'SELECT id, raison_sociale, wilaya, numero_cotisant FROM clients WHERE id = ?',
                [$ob['client_id']]
            );
            if (!$client) {
                continue;
            }
            $items[] = array_merge($ob, [
                'wilaya' => $client['wilaya'] ?? null,
                'numero_cotisant' => $client['numero_cotisant'] ?? null,
            ]);
        }

        return array_slice($items, 0, $limit);
    }
}
