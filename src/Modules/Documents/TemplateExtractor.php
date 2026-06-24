<?php

declare(strict_types=1);

namespace App\Modules\Documents;

final class TemplateExtractor
{
    public static function classify(string $text, string $filename): string
    {
        $lower = mb_strtolower($text . ' ' . $filename);
        if (str_contains($lower, 'bulletin de paie') || str_contains($lower, 'fiche de paie') || str_contains($lower, 'net a payer')) {
            return 'fiche_paie';
        }
        if (str_contains($lower, 'cacobatph') || str_contains($lower, 'conges payes') || str_contains($lower, 'chomage')) {
            return 'cacobatph_declaration';
        }
        if (str_contains($lower, 'securite sociale') || str_contains($lower, 'regime general') || preg_match('/\br22\b/i', $text)) {
            return 'cnas_declaration';
        }
        if (str_contains($lower, 'g. n') && str_contains($lower, '50')) {
            return 'g50';
        }
        if (str_contains($lower, 'g12') || str_contains($lower, 'impot forfaitaire')) {
            return 'g12';
        }
        if (str_contains($lower, 'facture')) {
            return 'facture';
        }
        if (str_contains($lower, 'releve') || str_contains($lower, 'bancaire')) {
            return 'releve_bancaire';
        }
        return 'unknown';
    }

    public static function extract(string $docType, string $text): array
    {
        return match ($docType) {
            'fiche_paie' => self::extractFichePaie($text),
            'cnas_declaration' => self::extractCnas($text),
            'cacobatph_declaration' => self::extractCacobatph($text),
            'g50' => self::extractG50($text),
            default => [],
        };
    }

    private static function extractFichePaie(string $text): array
    {
        $data = ['entry_type' => 'payroll', 'doc_type' => 'fiche_paie'];

        if (preg_match('/(?:JANVIER|FEVRIER|MARS|AVRIL|MAI|JUIN|JUILLET|AOUT|SEPTEMBRE|OCTOBRE|NOVEMBRE|DECEMBRE)\s+(\d{4})/iu', $text, $m)) {
            $months = ['JANVIER'=>1,'FEVRIER'=>2,'MARS'=>3,'AVRIL'=>4,'MAI'=>5,'JUIN'=>6,'JUILLET'=>7,'AOUT'=>8,'SEPTEMBRE'=>9,'OCTOBRE'=>10,'NOVEMBRE'=>11,'DECEMBRE'=>12];
            $monthName = mb_strtoupper($m[0]);
            foreach ($months as $name => $num) {
                if (str_contains($monthName, $name)) {
                    $data['period_month'] = $num;
                    break;
                }
            }
            $data['period_year'] = (int) $m[1];
        }

        if (preg_match('/NET\s+A\s+PAYER\s+([\d\s,\.]+)/iu', $text, $m)) {
            $data['net_a_payer'] = self::parseAmount($m[1]);
        }
        if (preg_match('/SALAIRE\s+DE\s+BASE[\s\S]{0,80}?([\d\s,\.]+)/iu', $text, $m)) {
            $data['salaire_base'] = self::parseAmount($m[1]);
            $data['masse_salariale'] = $data['salaire_base'];
        }
        if (preg_match('/NOM:\s*(\d+)/iu', $text)) {
            // employee matricule present
        }
        if (preg_match('/\n([A-Z][A-Z\s\-]+)\n/u', $text, $m)) {
            $name = trim($m[1]);
            if (!str_contains($name, 'BULLETIN') && strlen($name) > 5) {
                $data['employee_name'] = $name;
            }
        }
        if (preg_match('/(\d{10})/u', $text, $m)) {
            $data['numero_cotisant'] = $m[1];
        }

        $data['effectif'] = 1;
        return $data;
    }

    private static function extractCnas(string $text): array
    {
        $data = ['entry_type' => 'payroll', 'doc_type' => 'cnas_declaration'];

        if (preg_match('/(JANVIER|FEVRIER|MARS|AVRIL|MAI|JUIN|JUILLET|AOUT|SEPTEMBRE|OCTOBRE|NOVEMBRE|DECEMBRE)\s+(\d{4})/iu', $text, $m)) {
            $months = ['JANVIER'=>1,'FEVRIER'=>2,'MARS'=>3,'AVRIL'=>4,'MAI'=>5,'JUIN'=>6,'JUILLET'=>7,'AOUT'=>8,'SEPTEMBRE'=>9,'OCTOBRE'=>10,'NOVEMBRE'=>11,'DECEMBRE'=>12];
            $data['period_month'] = $months[mb_strtoupper($m[1])] ?? null;
            $data['period_year'] = (int) $m[2];
        }
        if (preg_match('/(\d+)e?\s*TRIMESTRE\s+(\d{4})/iu', $text, $m)) {
            $data['period_quarter'] = (int) $m[1];
            $data['period_year'] = (int) $m[2];
            $data['period_month'] = ((int) $m[1]) * 3;
        }

        if (preg_match('/R22[\s\S]{0,40}?([\d\s,\.]+)\s+34[,.]50\s*%?\s+([\d\s,\.]+)/iu', $text, $m)) {
            $data['masse_salariale'] = self::parseAmount($m[1]);
        } elseif (preg_match('/REGIME GENERAL\s+([\d\s,\.]+)\s+34[,.]50/iu', $text, $m)) {
            $data['masse_salariale'] = self::parseAmount($m[1]);
        }

        if (preg_match('/EFFECTIF TOTAL[\s\S]{0,30}?(\d+)/iu', $text, $m)) {
            $data['effectif'] = (int) $m[1];
        }
        if (preg_match('/(\d{10})/u', $text, $m)) {
            $data['numero_cotisant'] = $m[1];
        }

        return $data;
    }

    private static function extractCacobatph(string $text): array
    {
        $data = self::extractCnas($text);
        $data['doc_type'] = 'cacobatph_declaration';
        if (preg_match('/CONGES PAYES[\s\S]{0,40}?([\d\s,\.]+)\s+12[,.]21/iu', $text, $m)) {
            $data['masse_salariale'] = self::parseAmount($m[1]);
        }
        if (preg_match('/(\d+)\s*$/m', $text, $m)) {
            $data['nombre_assurees'] = (int) $m[1];
        }
        return $data;
    }

    private static function extractG50(string $text): array
    {
        $data = ['entry_type' => 'sales', 'doc_type' => 'g50'];
        if (preg_match('/Ann[eé]e:\s*(\d{4})/iu', $text, $m)) {
            $data['period_year'] = (int) $m[1];
        }
        if (preg_match('/Mois\s*:\s*(JANVIER|FEVRIER|MARS|AVRIL|MAI|JUIN|JUILLET|AOUT|SEPTEMBRE|OCTOBRE|NOVEMBRE|DECEMBRE)/iu', $text, $m)) {
            $months = ['JANVIER'=>1,'FEVRIER'=>2,'MARS'=>3,'AVRIL'=>4,'MAI'=>5,'JUIN'=>6,'JUILLET'=>7,'AOUT'=>8,'SEPTEMBRE'=>9,'OCTOBRE'=>10,'NOVEMBRE'=>11,'DECEMBRE'=>12];
            $data['period_month'] = $months[mb_strtoupper($m[1])] ?? null;
        }
        if (preg_match('/acompt[\s\S]{0,80}?([\d\s,\.]+)\s+0[,.]3\s+([\d\s,\.]+)/iu', $text, $m)) {
            $data['irg_acompte_base'] = self::parseAmount($m[1]);
        }
        return $data;
    }

    private static function parseAmount(string $raw): float
    {
        $clean = str_replace([' ', ','], ['', '.'], trim($raw));
        return (float) $clean;
    }
}
