<?php

declare(strict_types=1);

namespace App\Modules\Automation;

use App\Core\Database;
use App\Modules\Alerts\AlertService;

final class CalcEngine
{
    public static function onPayrollEntrySaved(int $payrollEntryId): void
    {
        $entry = Database::fetchOne(
            'SELECT pe.*, c.secteur, c.cnas_regime, c.raison_sociale, c.numero_cotisant
             FROM payroll_entries pe JOIN clients c ON c.id = pe.client_id WHERE pe.id = ?',
            [$payrollEntryId]
        );
        if (!$entry) {
            return;
        }

        $assiette = (float) $entry['masse_salariale'];
        $year = (int) $entry['period_year'];
        $month = (int) $entry['period_month'];
        $quarter = (int) ceil($month / 3);

        if ($entry['cnas_regime'] === 'MENSUEL') {
            self::upsertDeclaration(
                (int) $entry['client_id'],
                'CNAS_MENSUELLE',
                $year,
                $month,
                null,
                self::computeCnasLines($assiette, 'CNAS_MENSUELLE', $entry['secteur']),
                $payrollEntryId,
                null,
                $entry
            );
        } else {
            self::upsertDeclaration(
                (int) $entry['client_id'],
                'CNAS_TRIMESTRIELLE',
                $year,
                null,
                $quarter,
                self::computeCnasLines($assiette, 'CNAS_TRIMESTRIELLE', $entry['secteur']),
                $payrollEntryId,
                null,
                $entry
            );
        }

        if ($entry['secteur'] === 'BTP') {
            self::upsertDeclaration(
                (int) $entry['client_id'],
                'CACOBATPH',
                $year,
                null,
                $quarter,
                self::computeCacobatphLines($assiette, (int) ($entry['nombre_assurees'] ?? $entry['effectif'])),
                $payrollEntryId,
                null,
                $entry
            );
        }

        AlertService::syncForClient((int) $entry['client_id']);
    }

    public static function onSalesEntrySaved(int $salesEntryId): void
    {
        $entry = Database::fetchOne(
            'SELECT se.*, c.secteur, c.raison_sociale, c.regime_fiscal
             FROM sales_entries se JOIN clients c ON c.id = se.client_id WHERE se.id = ?',
            [$salesEntryId]
        );
        if (!$entry) {
            return;
        }

        $year = (int) $entry['period_year'];
        $month = $entry['period_month'] ? (int) $entry['period_month'] : null;
        $ifu = self::computeG12Lines($entry);

        self::upsertDeclaration(
            (int) $entry['client_id'],
            'G12',
            $year,
            null,
            null,
            $ifu,
            null,
            $salesEntryId,
            $entry
        );

        if ($entry['irg_acompte_base'] !== null && (float) $entry['irg_acompte_base'] > 0) {
            $base = (float) $entry['irg_acompte_base'];
            $g50 = self::computeG50Lines($entry, $base);
            self::upsertDeclaration(
                (int) $entry['client_id'],
                'G50',
                $year,
                $month,
                null,
                $g50,
                null,
                $salesEntryId,
                $entry
            );
        } elseif ((float) $entry['ca_biens'] > 0 || (float) $entry['ca_services'] > 0) {
            $g50 = self::computeG50Lines($entry, null);
            self::upsertDeclaration(
                (int) $entry['client_id'],
                'G50',
                $year,
                $month,
                null,
                $g50,
                null,
                $salesEntryId,
                $entry
            );
        }

        AlertService::syncForClient((int) $entry['client_id']);
    }

    /** @return array<int, array<string, mixed>> */
    private static function computeCnasLines(float $assiette, string $type, string $secteur): array
    {
        $rates = Database::fetchAll(
            'SELECT * FROM cotisation_rate_tables
             WHERE declaration_type = ? AND (secteur = ? OR secteur IS NULL)
             AND valid_from <= CURDATE() AND (valid_to IS NULL OR valid_to >= CURDATE())',
            [$type, $secteur]
        );

        $lines = [];
        $total = 0.0;
        foreach ($rates as $rate) {
            $taux = (float) $rate['taux'];
            $montant = round($assiette * ($taux / 100), 2);
            $lines[] = [
                'code' => $rate['code'],
                'label' => $rate['label'],
                'assiette' => $assiette,
                'taux' => $taux,
                'montant' => $montant,
            ];
            $total += $montant;
        }

        return [
            'assiette' => $assiette,
            'lines' => $lines,
            'total' => round($total, 2),
        ];
    }

    private static function computeCacobatphLines(float $assiette, int $assurees): array
    {
        $rates = Database::fetchAll(
            "SELECT * FROM cotisation_rate_tables WHERE declaration_type = 'CACOBATPH'
             AND valid_from <= CURDATE() AND (valid_to IS NULL OR valid_to >= CURDATE())"
        );

        $lines = [];
        $total = 0.0;
        foreach ($rates as $rate) {
            $taux = (float) $rate['taux'];
            $montant = round($assiette * ($taux / 100), 2);
            $lines[] = [
                'code' => $rate['code'],
                'label' => $rate['label'],
                'assiette' => $assiette,
                'taux' => $taux,
                'montant' => $montant,
                'nombre_assurees' => $assurees,
            ];
            $total += $montant;
        }

        return [
            'assiette' => $assiette,
            'nombre_assurees' => $assurees,
            'lines' => $lines,
            'total' => round($total, 2),
        ];
    }

    private static function computeG12Lines(array $entry): array
    {
        $lines = [];
        $total = 0.0;

        $mapping = [
            ['field' => 'ca_biens', 'code' => 'IFU_BIENS', 'rate' => 5.0, 'label' => 'Production/vente de biens'],
            ['field' => 'ca_services', 'code' => 'IFU_SERVICES', 'rate' => 12.0, 'label' => 'Prestations de services'],
            ['field' => 'ca_auto_entrepreneur', 'code' => 'IFU_AUTO', 'rate' => 0.5, 'label' => 'Auto-entrepreneur'],
        ];

        foreach ($mapping as $m) {
            $ca = (float) $entry[$m['field']];
            if ($ca <= 0) {
                continue;
            }
            $montant = round($ca * ($m['rate'] / 100), 2);
            $lines[] = [
                'code' => $m['code'],
                'label' => $m['label'],
                'ca' => $ca,
                'taux' => $m['rate'],
                'montant' => $montant,
            ];
            $total += $montant;
        }

        return [
            'period_year' => (int) $entry['period_year'],
            'lines' => $lines,
            'total' => round($total, 2),
            'minimum_imposition' => $entry['secteur'] === 'AUTO_ENTREPRENEUR' ? 10000 : 30000,
        ];
    }

    private static function computeG50Lines(array $entry, ?float $irgBase): array
    {
        $lines = [];
        $total = 0.0;
        $year = (int) $entry['period_year'];
        $month = $entry['period_month'] ? (int) $entry['period_month'] : null;

        $caBiens = (float) ($entry['ca_biens'] ?? 0);
        $caServices = (float) ($entry['ca_services'] ?? 0);

        if ($caBiens > 0) {
            $montant = round($caBiens * 0.09, 2);
            $lines[] = [
                'code' => 'TVA_9',
                'label' => 'TVA collectée — biens (9%)',
                'base' => $caBiens,
                'taux' => 9,
                'montant' => $montant,
            ];
            $total += $montant;
        }
        if ($caServices > 0) {
            $montant = round($caServices * 0.19, 2);
            $lines[] = [
                'code' => 'TVA_19',
                'label' => 'TVA collectée — services (19%)',
                'base' => $caServices,
                'taux' => 19,
                'montant' => $montant,
            ];
            $total += $montant;
        }
        if ($irgBase !== null && $irgBase > 0) {
            $montant = round($irgBase * 0.30, 2);
            $lines[] = [
                'code' => 'IRG_ACOMPTE',
                'label' => 'Acompte provisionnel IRG',
                'base' => $irgBase,
                'taux' => 30,
                'montant' => $montant,
            ];
            $total += $montant;
        }

        return [
            'raison_sociale' => $entry['raison_sociale'],
            'period_year' => $year,
            'period_month' => $month,
            'lines' => $lines,
            'total' => round($total, 2),
        ];
    }

    private static function upsertDeclaration(
        int $clientId,
        string $type,
        int $year,
        ?int $month,
        ?int $quarter,
        array $computed,
        ?int $payrollEntryId,
        ?int $salesEntryId,
        array $sourceEntry
    ): void {
        $existing = Database::fetchOne(
            'SELECT id, status FROM declarations
             WHERE client_id = ? AND type = ? AND period_year = ?
             AND (period_month <=> ?) AND (period_quarter <=> ?)
             AND status = ?',
            [$clientId, $type, $year, $month, $quarter, 'DRAFT_CALCULATED']
        );

        $computed['source'] = [
            'raison_sociale' => $sourceEntry['raison_sociale'] ?? null,
            'numero_cotisant' => $sourceEntry['numero_cotisant'] ?? null,
        ];
        $json = json_encode($computed, JSON_UNESCAPED_UNICODE);

        if ($existing) {
            Database::query(
                'UPDATE declarations SET computed_fields = ?, payroll_entry_id = COALESCE(?, payroll_entry_id),
                 sales_entry_id = COALESCE(?, sales_entry_id), updated_at = NOW() WHERE id = ?',
                [$json, $payrollEntryId, $salesEntryId, $existing['id']]
            );
            return;
        }

        Database::insert(
            'INSERT INTO declarations (client_id, type, period_year, period_month, period_quarter, status, computed_fields, payroll_entry_id, sales_entry_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$clientId, $type, $year, $month, $quarter, 'DRAFT_CALCULATED', $json, $payrollEntryId, $salesEntryId]
        );
    }
}
