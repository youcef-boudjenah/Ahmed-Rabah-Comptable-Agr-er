<?php

declare(strict_types=1);

namespace App\Modules\Production;

use App\Core\Database;
use App\Modules\Automation\DeadlineService;
use App\Modules\Automation\WorkflowService;

final class ProductionService
{
    /** @return array<string, mixed> */
    public static function monthly(int $cabinetId, int $year, int $month, array $filters = []): array
    {
        $month = max(1, min(12, $month));
        $statusFilter = $filters['status'] ?? '';
        $typeFilter = $filters['type'] ?? '';
        $q = trim($filters['q'] ?? '');

        $clients = Database::fetchAll(
            'SELECT id, raison_sociale, secteur, cnas_regime, numero_cotisant, contact_email, contact_phone
             FROM clients WHERE cabinet_id = ? AND is_active = 1 ORDER BY raison_sociale',
            [$cabinetId]
        );

        $rows = [];
        $stats = [
            'total_clients' => count($clients),
            'total_obligations' => 0,
            'missing_data' => 0,
            'draft_ready' => 0,
            'approved' => 0,
            'submitted' => 0,
            'overdue' => 0,
            'total_amount' => 0.0,
        ];

        foreach ($clients as $client) {
            if ($q !== '' && !str_contains(mb_strtolower($client['raison_sociale']), mb_strtolower($q))) {
                continue;
            }

            foreach (self::obligationsForMonth($client, $year, $month) as $ob) {
                if ($typeFilter !== '' && $ob['type'] !== $typeFilter) {
                    continue;
                }
                if ($statusFilter !== '' && $ob['status'] !== $statusFilter) {
                    continue;
                }

                $ob['client_id'] = (int) $client['id'];
                $ob['raison_sociale'] = $client['raison_sociale'];
                $ob['secteur'] = $client['secteur'];
                $ob['numero_cotisant'] = $client['numero_cotisant'];
                $ob['contact_email'] = $client['contact_email'];
                $ob['contact_phone'] = $client['contact_phone'];
                $ob['action_url'] = self::actionUrl($ob);
                $ob['status_badge'] = self::statusBadgeClass($ob['status']);

                $rows[] = $ob;
                $stats['total_obligations']++;
                if (isset($stats[$ob['status']])) {
                    $stats[$ob['status']]++;
                }
                if ($ob['amount'] && $ob['status'] !== 'submitted') {
                    $stats['total_amount'] += (float) $ob['amount'];
                }
            }
        }

        usort($rows, static function ($a, $b) {
            $prio = ['overdue' => 0, 'missing_data' => 1, 'pending_calc' => 2, 'draft_ready' => 3, 'approved' => 4, 'submitted' => 5];
            $pa = $prio[$a['status']] ?? 9;
            $pb = $prio[$b['status']] ?? 9;
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }
            return strcasecmp($a['raison_sociale'], $b['raison_sociale']);
        });

        $stats['total_amount'] = round($stats['total_amount'], 2);
        $stats['completion_pct'] = $stats['total_obligations'] > 0
            ? (int) round(($stats['submitted'] / $stats['total_obligations']) * 100)
            : 0;
        $stats['without_contact'] = count(array_unique(array_map(
            fn ($r) => $r['client_id'],
            array_filter($rows, fn ($r) => in_array($r['status'], ['missing_data', 'overdue'], true)
                && empty($r['contact_phone']) && empty($r['contact_email']))
        )));

        return [
            'year' => $year,
            'month' => $month,
            'month_label' => DeadlineService::periodLabel('CNAS_MENSUELLE', ['year' => $year, 'month' => $month, 'quarter' => null]),
            'rows' => $rows,
            'stats' => $stats,
            'filters' => compact('statusFilter', 'typeFilter', 'q'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function obligationsForMonth(array $client, int $year, int $month): array
    {
        $clientId = (int) $client['id'];
        $items = [];
        $types = [];

        if ($client['cnas_regime'] === 'MENSUEL') {
            $types[] = 'CNAS_MENSUELLE';
        } else {
            if ($month % 3 === 0) {
                $types[] = 'CNAS_TRIMESTRIELLE';
            }
        }

        if ($client['secteur'] === 'BTP' && $month % 3 === 0) {
            $types[] = 'CACOBATPH';
        }

        $types[] = 'G50';

        foreach ($types as $type) {
            $period = self::periodForType($type, $year, $month);
            if ($period === null) {
                continue;
            }

            $decl = Database::fetchOne(
                'SELECT * FROM declarations WHERE client_id = ? AND type = ? AND period_year = ?
                 AND (period_month <=> ?) AND (period_quarter <=> ?)
                 ORDER BY created_at DESC LIMIT 1',
                [$clientId, $type, $period['year'], $period['month'], $period['quarter']]
            );

            $hasPayroll = (bool) Database::fetchOne(
                'SELECT id FROM payroll_entries WHERE client_id = ? AND period_year = ? AND period_month = ?',
                [$clientId, $period['year'], $period['month'] ?? $month]
            );
            $hasSales = (bool) Database::fetchOne(
                'SELECT id FROM sales_entries WHERE client_id = ? AND period_year = ? AND (period_month = ? OR period_month IS NULL)',
                [$clientId, $period['year'], $period['month']]
            );

            $hasSource = in_array($type, ['CNAS_MENSUELLE', 'CNAS_TRIMESTRIELLE', 'CACOBATPH'], true)
                ? self::hasPayrollForPeriod($clientId, $period)
                : $hasSales;

            $dueDate = self::dueDateFor($type, $period);
            $status = self::resolveStatus($decl, $hasSource, $dueDate);
            $amount = $decl ? (float) (json_decode($decl['computed_fields'], true)['total'] ?? 0) : null;

            $items[] = [
                'type' => $type,
                'type_label' => DeadlineService::typeLabel($type),
                'period' => $period,
                'period_label' => DeadlineService::periodLabel($type, $period),
                'due_date' => $dueDate,
                'due_label' => $dueDate->format('d/m/Y'),
                'days_left' => (int) (new \DateTime('today'))->diff($dueDate)->format('%r%a'),
                'status' => $status,
                'status_label' => DeadlineService::statusLabel($status),
                'amount' => $amount,
                'declaration_id' => $decl['id'] ?? null,
                'declaration_status' => $decl['status'] ?? null,
                'has_payroll' => $hasPayroll,
                'has_sales' => $hasSales,
            ];
        }

        return $items;
    }

    /** @return array{year: int, month: int|null, quarter: int|null}|null */
    private static function periodForType(string $type, int $year, int $month): ?array
    {
        if ($type === 'CNAS_MENSUELLE' || $type === 'G50') {
            return ['year' => $year, 'month' => $month, 'quarter' => null];
        }
        if ($type === 'CNAS_TRIMESTRIELLE' || $type === 'CACOBATPH') {
            $q = (int) ceil($month / 3);
            return ['year' => $year, 'month' => null, 'quarter' => $q];
        }
        return null;
    }

    private static function hasPayrollForPeriod(int $clientId, array $period): bool
    {
        if ($period['month']) {
            return (bool) Database::fetchOne(
                'SELECT id FROM payroll_entries WHERE client_id = ? AND period_year = ? AND period_month = ?',
                [$clientId, $period['year'], $period['month']]
            );
        }
        if ($period['quarter']) {
            $start = ($period['quarter'] - 1) * 3 + 1;
            $end = $period['quarter'] * 3;
            return (bool) Database::fetchOne(
                'SELECT id FROM payroll_entries WHERE client_id = ? AND period_year = ? AND period_month BETWEEN ? AND ?',
                [$clientId, $period['year'], $start, $end]
            );
        }
        return false;
    }

    private static function dueDateFor(string $type, array $period): \DateTime
    {
        $rule = Database::fetchOne('SELECT due_day FROM deadline_rules WHERE declaration_type = ?', [$type]);
        $dueDay = (int) ($rule['due_day'] ?? 20);

        if ($period['quarter']) {
            $endMonth = $period['quarter'] * 3;
            $dueMonth = $endMonth + 1;
            $dueYear = $period['year'];
            if ($dueMonth > 12) {
                $dueMonth = 1;
                $dueYear++;
            }
            return new \DateTime(sprintf('%d-%02d-%02d', $dueYear, $dueMonth, min($dueDay, 28)));
        }

        $dueMonth = ($period['month'] ?? 1) + 1;
        $dueYear = $period['year'];
        if ($dueMonth > 12) {
            $dueMonth = 1;
            $dueYear++;
        }
        return new \DateTime(sprintf('%d-%02d-%02d', $dueYear, $dueMonth, min($dueDay, 28)));
    }

    private static function resolveStatus(?array $decl, bool $hasSource, \DateTime $dueDate): string
    {
        $today = new \DateTime('today');
        if ($decl) {
            if ($decl['status'] === 'SUBMITTED') {
                return 'submitted';
            }
            if ($decl['status'] === 'APPROVED') {
                return $dueDate < $today ? 'overdue' : 'approved';
            }
            return 'draft_ready';
        }
        if (!$hasSource) {
            return $dueDate < $today ? 'overdue' : 'missing_data';
        }
        return $dueDate < $today ? 'overdue' : 'pending_calc';
    }

    private static function actionUrl(array $ob): string
    {
        if ($ob['declaration_id']) {
            return '/declarations/' . $ob['declaration_id'];
        }
        if (in_array($ob['status'], ['missing_data', 'pending_calc'], true)) {
            return WorkflowService::entryUrlForType($ob['type'], (int) $ob['client_id']);
        }
        return '/clients/' . $ob['client_id'];
    }

    private static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'submitted' => 'badge-success',
            'approved' => 'badge-info',
            'draft_ready' => 'badge-warning',
            'missing_data' => 'badge-warning',
            'overdue' => 'badge-danger',
            default => 'badge-neutral',
        };
    }
}
