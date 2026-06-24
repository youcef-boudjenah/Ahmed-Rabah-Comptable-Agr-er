<?php

declare(strict_types=1);

namespace App\Modules\Automation;

use App\Core\Database;

final class DeadlineService
{
  private const MONTHS_FR = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
    7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
  ];

  /** @return array<int, array<string, mixed>> */
  public static function clientObligations(int $clientId, ?int $year = null): array
  {
    $client = Database::fetchOne('SELECT * FROM clients WHERE id = ?', [$clientId]);
    if (!$client) {
      return [];
    }

    $year = $year ?? (int) date('Y');
    $types = self::applicableTypes($client);
    $obligations = [];

    foreach ($types as $type) {
      foreach (self::periodsForType($type, $year, $client) as $period) {
        $decl = self::findDeclaration($clientId, $type, $period);
        $hasSource = self::hasSourceData($clientId, $type, $period);
        $dueDate = self::computeDueDate($type, $period);
        $status = self::resolveStatus($decl, $hasSource, $dueDate);
        $amount = $decl ? (float) (json_decode($decl['computed_fields'], true)['total'] ?? 0) : null;

        $obligations[] = [
          'type' => $type,
          'type_label' => self::typeLabel($type),
          'period_label' => self::periodLabel($type, $period),
          'period' => $period,
          'due_date' => $dueDate,
          'due_label' => $dueDate->format('d/m/Y'),
          'days_left' => (int) (new \DateTime('today'))->diff($dueDate)->format('%r%a'),
          'status' => $status,
          'status_label' => self::statusLabel($status),
          'amount' => $amount,
          'declaration_id' => $decl['id'] ?? null,
          'declaration_status' => $decl['status'] ?? null,
        ];
      }
    }

    usort($obligations, fn ($a, $b) => $a['due_date'] <=> $b['due_date']);
    return $obligations;
  }

  /** @return array<int, array<string, mixed>> */
  public static function cabinetUpcoming(int $cabinetId, int $days = 30): array
  {
    $clients = Database::fetchAll('SELECT id, raison_sociale, secteur FROM clients WHERE cabinet_id = ? AND is_active = 1', [$cabinetId]);
    $items = [];
    $today = new \DateTime('today');
    $limit = (clone $today)->modify("+{$days} days");

    foreach ($clients as $client) {
      foreach (self::clientObligations((int) $client['id']) as $ob) {
        if ($ob['due_date'] > $limit || $ob['status'] === 'submitted') {
          continue;
        }
        $ob['client_id'] = (int) $client['id'];
        $ob['raison_sociale'] = $client['raison_sociale'];
        $ob['secteur'] = $client['secteur'];
        $items[] = $ob;
      }
    }

    usort($items, fn ($a, $b) => $a['due_date'] <=> $b['due_date']);
    return $items;
  }

  public static function complianceScore(int $clientId): int
  {
    $obligations = self::clientObligations($clientId);
    if ($obligations === []) {
      return 100;
    }
    $today = new \DateTime('today');
    $relevant = array_filter($obligations, fn ($o) => $o['due_date'] <= $today || $o['due_date'] <= (clone $today)->modify('+20 days'));
    if ($relevant === []) {
      return 100;
    }
    $ok = count(array_filter($relevant, fn ($o) => in_array($o['status'], ['submitted', 'approved', 'draft_ready'], true)));
    return (int) round(($ok / count($relevant)) * 100);
  }

  public static function cabinetStats(int $cabinetId): array
  {
    $upcoming = self::cabinetUpcoming($cabinetId, 30);
    $overdue = array_filter($upcoming, fn ($o) => $o['status'] === 'overdue');
    $missing = array_filter($upcoming, fn ($o) => $o['status'] === 'missing_data');
    $drafts = array_filter($upcoming, fn ($o) => $o['status'] === 'draft_ready');
    $dueThisMonth = array_filter($upcoming, fn ($o) => $o['due_date']->format('Y-m') === date('Y-m'));
    $totalDue = array_sum(array_map(fn ($o) => $o['amount'] ?? 0, array_filter($upcoming, fn ($o) => $o['amount'] && $o['status'] !== 'submitted')));

    return [
      'overdue_count' => count($overdue),
      'missing_data_count' => count($missing),
      'draft_ready_count' => count($drafts),
      'due_this_month' => count($dueThisMonth),
      'total_amount_due' => round($totalDue, 2),
      'upcoming' => array_slice($upcoming, 0, 15),
    ];
  }

  /** Top clients needing attention — optimized for large portfolios */
  public static function topClientsNeedingAttention(int $cabinetId, int $limit = 10): array
  {
    $limit = max(1, min(50, $limit));
    $rows = Database::fetchAll(
      "SELECT c.id, c.raison_sociale, c.secteur,
              (SELECT COUNT(*) FROM alerts a WHERE a.client_id = c.id AND a.is_read = 0 AND a.severity = 'critical') AS critical_count,
              (SELECT COUNT(*) FROM alerts a WHERE a.client_id = c.id AND a.is_read = 0 AND a.severity = 'warning') AS warning_count,
              (SELECT COUNT(*) FROM declarations d WHERE d.client_id = c.id AND d.status = 'DRAFT_CALCULATED') AS draft_count
       FROM clients c
       WHERE c.cabinet_id = ? AND c.is_active = 1
       HAVING (critical_count + warning_count + draft_count) > 0
       ORDER BY critical_count DESC, warning_count DESC, draft_count DESC, c.raison_sociale ASC
       LIMIT {$limit}",
      [$cabinetId]
    );

    return array_map(function (array $c) {
      $score = max(0, 100 - (int) $c['critical_count'] * 25 - (int) $c['warning_count'] * 10);
      return [
        'id' => (int) $c['id'],
        'raison_sociale' => $c['raison_sociale'],
        'secteur' => $c['secteur'],
        'compliance' => $score,
        'overdue' => (int) $c['critical_count'],
        'missing' => (int) $c['warning_count'],
        'next_obligation' => null,
        'ytd_cotisations' => 0,
      ];
    }, $rows);
  }

  /** @return array<int, array<string, mixed>> */
  public static function clientsWithStatus(int $cabinetId): array
  {
    $clients = Database::fetchAll('SELECT * FROM clients WHERE cabinet_id = ? AND is_active = 1 ORDER BY raison_sociale', [$cabinetId]);
    return array_map(function (array $c) {
      $obligations = self::clientObligations((int) $c['id']);
      $next = $obligations[0] ?? null;
      $overdue = count(array_filter($obligations, fn ($o) => $o['status'] === 'overdue'));
      $missing = count(array_filter($obligations, fn ($o) => $o['status'] === 'missing_data'));
      return [
        'id' => (int) $c['id'],
        'raison_sociale' => $c['raison_sociale'],
        'secteur' => $c['secteur'],
        'compliance' => self::complianceScore((int) $c['id']),
        'overdue' => $overdue,
        'missing' => $missing,
        'next_obligation' => $next,
        'ytd_cotisations' => self::ytdTotal((int) $c['id']),
      ];
    }, $clients);
  }

  private static function ytdTotal(int $clientId): float
  {
    $rows = Database::fetchAll(
      "SELECT computed_fields FROM declarations WHERE client_id = ? AND period_year = ? AND status IN ('DRAFT_CALCULATED','APPROVED','SUBMITTED')",
      [$clientId, (int) date('Y')]
    );
    $total = 0.0;
    foreach ($rows as $r) {
      $cf = json_decode($r['computed_fields'], true);
      $total += (float) ($cf['total'] ?? 0);
    }
    return round($total, 2);
  }

  /** @return string[] */
  private static function applicableTypes(array $client): array
  {
    $types = $client['cnas_regime'] === 'MENSUEL' ? ['CNAS_MENSUELLE'] : ['CNAS_TRIMESTRIELLE'];
    if ($client['secteur'] === 'BTP') {
      $types[] = 'CACOBATPH';
    }
    $types[] = 'G50';
    $types[] = 'G12';
    if ((int) date('n') <= 2 || (int) date('n') >= 11) {
      $types[] = 'G12_BIS';
    }
    return $types;
  }

  /** @return array<int, array<string, int|null>> */
  private static function periodsForType(string $type, int $year, array $client): array
  {
    $now = (int) date('n');
    $periods = [];

    if ($type === 'CNAS_MENSUELLE') {
      for ($m = 1; $m <= $now; $m++) {
        $periods[] = ['year' => $year, 'month' => $m, 'quarter' => null];
      }
    } elseif ($type === 'CNAS_TRIMESTRIELLE' || $type === 'CACOBATPH') {
      $maxQ = (int) ceil($now / 3);
      for ($q = 1; $q <= $maxQ; $q++) {
        $periods[] = ['year' => $year, 'month' => null, 'quarter' => $q];
      }
    } elseif ($type === 'G50') {
      for ($m = 1; $m <= $now; $m++) {
        $periods[] = ['year' => $year, 'month' => $m, 'quarter' => null];
      }
    } elseif ($type === 'G12') {
      $periods[] = ['year' => $year, 'month' => null, 'quarter' => null];
    } elseif ($type === 'G12_BIS') {
      $periods[] = ['year' => $year - 1, 'month' => null, 'quarter' => null];
    }

    return $periods;
  }

  private static function findDeclaration(int $clientId, string $type, array $period): ?array
  {
    return Database::fetchOne(
      'SELECT * FROM declarations WHERE client_id = ? AND type = ? AND period_year = ?
       AND (period_month <=> ?) AND (period_quarter <=> ?)
       ORDER BY created_at DESC LIMIT 1',
      [$clientId, $type, $period['year'], $period['month'], $period['quarter']]
    );
  }

  private static function hasSourceData(int $clientId, string $type, array $period): bool
  {
    if (in_array($type, ['CNAS_MENSUELLE', 'CNAS_TRIMESTRIELLE', 'CACOBATPH'], true)) {
      if ($period['month']) {
        return (bool) Database::fetchOne(
          'SELECT id FROM payroll_entries WHERE client_id = ? AND period_year = ? AND period_month = ?',
          [$clientId, $period['year'], $period['month']]
        );
      }
      if ($period['quarter']) {
        $startMonth = ($period['quarter'] - 1) * 3 + 1;
        $endMonth = $period['quarter'] * 3;
        return (bool) Database::fetchOne(
          'SELECT id FROM payroll_entries WHERE client_id = ? AND period_year = ? AND period_month BETWEEN ? AND ?',
          [$clientId, $period['year'], $startMonth, $endMonth]
        );
      }
    }
    if (in_array($type, ['G50', 'G12', 'G12_BIS'], true)) {
      return (bool) Database::fetchOne(
        'SELECT id FROM sales_entries WHERE client_id = ? AND period_year = ?',
        [$clientId, $period['year']]
      );
    }
    return false;
  }

  private static function computeDueDate(string $type, array $period): \DateTime
  {
    $rule = Database::fetchOne('SELECT * FROM deadline_rules WHERE declaration_type = ?', [$type]);
    $dueDay = (int) ($rule['due_day'] ?? 20);

    if ($type === 'G12') {
      return new \DateTime(sprintf('%d-06-30', $period['year']));
    }
    if ($type === 'G12_BIS') {
      return new \DateTime(sprintf('%d-01-20', $period['year'] + 1));
    }
    if ($period['quarter']) {
      $endMonth = $period['quarter'] * 3;
      $dueMonth = $endMonth + 1;
      $dueYear = $period['year'];
      if ($dueMonth > 12) {
        $dueMonth = 1;
        $dueYear++;
      }
      return new \DateTime(sprintf('%d-%02d-%02d', $dueYear, $dueMonth, min($dueDay, (int) date('t', mktime(0, 0, 0, $dueMonth, 1, $dueYear)))));
    }
    $dueMonth = ($period['month'] ?? 1) + 1;
    $dueYear = $period['year'];
    if ($dueMonth > 12) {
      $dueMonth = 1;
      $dueYear++;
    }
    return new \DateTime(sprintf('%d-%02d-%02d', $dueYear, $dueMonth, min($dueDay, (int) date('t', mktime(0, 0, 0, $dueMonth, 1, $dueYear)))));
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

  public static function typeLabel(string $type): string
  {
    return match ($type) {
      'CNAS_MENSUELLE' => 'CNAS mensuelle',
      'CNAS_TRIMESTRIELLE' => 'CNAS trimestrielle',
      'CACOBATPH' => 'CACOBATPH',
      'G50' => 'G50 — Impôts & taxes',
      'G12' => 'G12 — IFU prévisionnelle',
      'G12_BIS' => 'G12 Bis — IFU définitive',
      default => $type,
    };
  }

  public static function periodLabel(string $type, array $period): string
  {
    if ($period['quarter']) {
      return $period['quarter'] . 'er trimestre ' . $period['year'];
    }
    if ($period['month']) {
      return (self::MONTHS_FR[$period['month']] ?? '') . ' ' . $period['year'];
    }
    if ($type === 'G12_BIS') {
      return 'Exercice ' . $period['year'];
    }
    return 'Année ' . $period['year'];
  }

  public static function statusLabel(string $status): string
  {
    return match ($status) {
      'submitted' => 'Déposée',
      'approved' => 'Approuvée — à déposer',
      'draft_ready' => 'Brouillon prêt',
      'missing_data' => 'Données manquantes',
      'pending_calc' => 'À calculer',
      'overdue' => 'En retard',
      default => $status,
    };
  }

  public static function statusColor(string $status): string
  {
    return match ($status) {
      'submitted' => 'bg-emerald-100 text-emerald-800',
      'approved' => 'bg-blue-100 text-blue-800',
      'draft_ready' => 'bg-amber-100 text-amber-800',
      'missing_data' => 'bg-orange-100 text-orange-800',
      'overdue' => 'bg-red-100 text-red-800',
      default => 'bg-slate-100 text-slate-600',
    };
  }
}
