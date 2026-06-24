<?php

declare(strict_types=1);

namespace App\Modules\Clients;

use App\Core\Database;
use App\Modules\Automation\DeadlineService;

final class ClientService
{
  /** @return array<string, mixed> */
  public static function profile(int $clientId): array
  {
    $payroll = Database::fetchAll(
      'SELECT * FROM payroll_entries WHERE client_id = ? ORDER BY period_year DESC, period_month DESC LIMIT 12',
      [$clientId]
    );
    $sales = Database::fetchAll(
      'SELECT * FROM sales_entries WHERE client_id = ? ORDER BY period_year DESC, period_month DESC LIMIT 6',
      [$clientId]
    );
    $declarations = Database::fetchAll(
      'SELECT * FROM declarations WHERE client_id = ? ORDER BY created_at DESC LIMIT 20',
      [$clientId]
    );
    foreach ($declarations as &$d) {
      $d['computed_fields'] = json_decode($d['computed_fields'], true) ?: [];
    }
    unset($d);

    $documents = Database::fetchAll(
      'SELECT * FROM documents WHERE client_id = ? ORDER BY created_at DESC LIMIT 10',
      [$clientId]
    );

    $cnasTrend = [];
    foreach ($payroll as $p) {
      $decl = Database::fetchOne(
        "SELECT computed_fields FROM declarations WHERE client_id = ? AND type IN ('CNAS_MENSUELLE','CNAS_TRIMESTRIELLE')
         AND payroll_entry_id = ? OR (period_year = ? AND period_month = ?) LIMIT 1",
        [$clientId, $p['id'], $p['period_year'], $p['period_month']]
      );
      $total = $decl ? (float) (json_decode($decl['computed_fields'], true)['total'] ?? 0) : 0;
      $cnasTrend[] = [
        'label' => sprintf('%02d/%d', $p['period_month'], $p['period_year']),
        'masse' => (float) $p['masse_salariale'],
        'cotisations' => $total,
      ];
    }
    $cnasTrend = array_reverse($cnasTrend);

    return [
      'obligations' => DeadlineService::clientObligations($clientId),
      'compliance' => DeadlineService::complianceScore($clientId),
      'payroll' => $payroll,
      'sales' => $sales,
      'declarations' => $declarations,
      'documents' => $documents,
      'cnas_trend' => $cnasTrend,
      'ytd_cotisations' => self::ytdCotisations($clientId),
      'notes' => ClientNoteRepository::forClient($clientId),
      'activity' => \App\Modules\Reports\ReportsService::clientActivity($clientId),
    ];
  }

  private static function ytdCotisations(int $clientId): float
  {
    $rows = Database::fetchAll(
      "SELECT computed_fields FROM declarations WHERE client_id = ? AND period_year = ?",
      [$clientId, (int) date('Y')]
    );
    $t = 0.0;
    foreach ($rows as $r) {
      $t += (float) (json_decode($r['computed_fields'], true)['total'] ?? 0);
    }
    return round($t, 2);
  }
}
