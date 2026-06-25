<?php

declare(strict_types=1);

namespace App\Modules\Outils;

use App\Core\Database;

final class AccountingToolsService
{
    /** @return array<string, mixed> */
    public static function cnas(float $assiette, string $secteur = 'BTP', string $type = 'CNAS_MENSUELLE'): array
    {
        $rates = Database::fetchAll(
            'SELECT * FROM cotisation_rate_tables
             WHERE declaration_type = ? AND (secteur = ? OR secteur IS NULL)
             AND valid_from <= CURDATE() AND (valid_to IS NULL OR valid_to >= CURDATE())',
            [$type, $secteur]
        );

        return self::linesFromRates($assiette, $rates, ['assiette' => $assiette, 'type' => $type, 'secteur' => $secteur]);
    }

    /** @return array<string, mixed> */
    public static function cacobatph(float $assiette, int $assurees): array
    {
        $rates = Database::fetchAll(
            "SELECT * FROM cotisation_rate_tables WHERE declaration_type = 'CACOBATPH'
             AND valid_from <= CURDATE() AND (valid_to IS NULL OR valid_to >= CURDATE())"
        );

        $result = self::linesFromRates($assiette, $rates, [
            'assiette' => $assiette,
            'nombre_assurees' => $assurees,
        ]);
        foreach ($result['lines'] as &$line) {
            $line['nombre_assurees'] = $assurees;
        }
        unset($line);

        return $result;
    }

    /** @return array<string, mixed> */
    public static function tva(float $montant, float $taux, string $mode = 'ht'): array
    {
        $taux = in_array($taux, [9.0, 19.0], true) ? $taux : 19.0;
        if ($mode === 'ttc') {
            $ht = round($montant / (1 + $taux / 100), 2);
            $tva = round($montant - $ht, 2);
            $ttc = $montant;
        } else {
            $ht = $montant;
            $tva = round($ht * ($taux / 100), 2);
            $ttc = round($ht + $tva, 2);
        }

        return [
            'mode' => $mode,
            'taux' => $taux,
            'ht' => $ht,
            'tva' => $tva,
            'ttc' => $ttc,
            'label' => $taux === 9.0 ? 'TVA biens (9 %)' : 'TVA services (19 %)',
        ];
    }

    /** @return array<string, mixed> */
    public static function g50(float $caBiens, float $caServices, ?float $irgBase): array
    {
        $lines = [];
        $total = 0.0;
        if ($caBiens > 0) {
            $m = round($caBiens * 0.09, 2);
            $lines[] = ['code' => 'TVA_9', 'label' => 'TVA collectée — biens', 'base' => $caBiens, 'taux' => 9, 'montant' => $m];
            $total += $m;
        }
        if ($caServices > 0) {
            $m = round($caServices * 0.19, 2);
            $lines[] = ['code' => 'TVA_19', 'label' => 'TVA collectée — services', 'base' => $caServices, 'taux' => 19, 'montant' => $m];
            $total += $m;
        }
        if ($irgBase !== null && $irgBase > 0) {
            $m = round($irgBase * 0.30, 2);
            $lines[] = ['code' => 'IRG_ACOMPTE', 'label' => 'Acompte provisionnel IRG (30 %)', 'base' => $irgBase, 'taux' => 30, 'montant' => $m];
            $total += $m;
        }

        return ['lines' => $lines, 'total' => round($total, 2)];
    }

    /** @return array<string, mixed> */
    public static function g12(float $caBiens, float $caServices, float $caAuto, string $secteur = 'SERVICES'): array
    {
        $mapping = [
            ['ca' => $caBiens, 'code' => 'IFU_BIENS', 'rate' => 5.0, 'label' => 'Production / vente de biens'],
            ['ca' => $caServices, 'code' => 'IFU_SERVICES', 'rate' => 12.0, 'label' => 'Prestations de services'],
            ['ca' => $caAuto, 'code' => 'IFU_AUTO', 'rate' => 0.5, 'label' => 'Auto-entrepreneur'],
        ];
        $lines = [];
        $total = 0.0;
        foreach ($mapping as $m) {
            if ($m['ca'] <= 0) {
                continue;
            }
            $montant = round($m['ca'] * ($m['rate'] / 100), 2);
            $lines[] = ['code' => $m['code'], 'label' => $m['label'], 'ca' => $m['ca'], 'taux' => $m['rate'], 'montant' => $montant];
            $total += $montant;
        }

        return [
            'lines' => $lines,
            'total' => round($total, 2),
            'minimum_imposition' => $secteur === 'AUTO_ENTREPRENEUR' ? 10000 : 30000,
        ];
    }

    /**
     * Estimation IRG mensuel salarié — indicative (barème progressif simplifié).
     * @return array<string, mixed>
     */
    public static function irgSalaire(float $salaireBrut): array
    {
        $cotisationSalarie = round($salaireBrut * 0.09, 2);
        $imposable = max(0, $salaireBrut - $cotisationSalarie);

        $brackets = [
            [30000, 0.0],
            [120000, 0.20],
            [360000, 0.30],
            [1440000, 0.35],
            [PHP_FLOAT_MAX, 0.40],
        ];

        $irg = 0.0;
        $prev = 0.0;
        $detail = [];
        foreach ($brackets as [$ceiling, $rate]) {
            if ($imposable <= $prev) {
                break;
            }
            $slice = min($imposable, $ceiling) - $prev;
            if ($slice > 0 && $rate > 0) {
                $part = round($slice * $rate, 2);
                $irg += $part;
                $detail[] = [
                    'tranche' => number_format($prev + 1, 0, ',', ' ') . ' – ' . ($ceiling < PHP_FLOAT_MAX ? number_format($ceiling, 0, ',', ' ') : '∞'),
                    'taux' => $rate * 100,
                    'montant' => $part,
                ];
            }
            $prev = $ceiling;
        }

        $netEstime = round($salaireBrut - $cotisationSalarie - $irg, 2);

        return [
            'salaire_brut' => $salaireBrut,
            'cotisation_salarie_9' => $cotisationSalarie,
            'base_imposable' => $imposable,
            'irg' => round($irg, 2),
            'net_estime' => $netEstime,
            'detail' => $detail,
            'disclaimer' => 'Estimation indicative — abattements, exonérations et barème exact à vérifier (LF / CGI).',
        ];
    }

    /** @return array<string, mixed> */
    public static function amortissementLineaire(float $valeur, int $dureeAnnees): array
    {
        $dureeAnnees = max(1, $dureeAnnees);
        $annuel = round($valeur / $dureeAnnees, 2);
        $plan = [];
        $reste = $valeur;
        for ($y = 1; $y <= $dureeAnnees; $y++) {
            $dot = $y === $dureeAnnees ? round($reste, 2) : $annuel;
            $reste -= $dot;
            $plan[] = ['annee' => $y, 'dotation' => $dot, 'cumul' => round($valeur - max(0, $reste), 2)];
        }

        return [
            'valeur_origine' => $valeur,
            'duree' => $dureeAnnees,
            'dotation_annuelle' => $annuel,
            'plan' => $plan,
        ];
    }

    /** @param list<array<string, mixed>> $rates */
    private static function linesFromRates(float $assiette, array $rates, array $meta): array
    {
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

        return array_merge($meta, ['lines' => $lines, 'total' => round($total, 2)]);
    }
}
