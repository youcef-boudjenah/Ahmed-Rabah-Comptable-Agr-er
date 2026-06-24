<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight XLSX reader (first sheet) — no external dependency.
 */
final class SimpleSpreadsheetReader
{
    /** @return list<list<string>> */
    public static function rows(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'txt') {
            return self::readCsv($filePath);
        }
        if ($ext === 'xlsx') {
            return self::readXlsx($filePath);
        }
        throw new \RuntimeException('Format non supporté. Utilisez .xlsx ou .csv');
    }

    /** @return list<list<string>> */
    private static function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Impossible de lire le fichier CSV.');
        }
        $firstLine = fgets($handle) ?: '';
        rewind($handle);
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map(static fn ($v) => trim((string) $v), $row);
        }
        fclose($handle);
        return $rows;
    }

    /** @return list<list<string>> */
    private static function readXlsx(string $filePath): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Extension ZipArchive requise pour lire les fichiers Excel.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Fichier Excel invalide.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml = $sharedXml) {
            $sharedStrings = self::parseSharedStrings($sharedStringsXml);
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new \RuntimeException('Feuille Excel introuvable.');
        }

        return self::parseSheet($sheetXml, $sharedStrings);
    }

    /** @return list<string> */
    private static function parseSharedStrings(string $xml): array
    {
        $doc = new \DOMDocument();
        @$doc->loadXML($xml);
        $strings = [];
        foreach ($doc->getElementsByTagName('si') as $si) {
            $text = '';
            foreach ($si->getElementsByTagName('t') as $t) {
                $text .= $t->textContent;
            }
            $strings[] = $text;
        }
        return $strings;
    }

    /** @return list<list<string>> */
    private static function parseSheet(string $xml, array $sharedStrings): array
    {
        $doc = new \DOMDocument();
        @$doc->loadXML($xml);
        $rows = [];
        $rowIndex = 0;

        foreach ($doc->getElementsByTagName('row') as $rowEl) {
            $cells = [];
            $colIndex = 0;
            foreach ($rowEl->getElementsByTagName('c') as $cell) {
                $ref = $cell->getAttribute('r');
                $col = self::colIndexFromRef($ref);
                while ($colIndex < $col) {
                    $cells[$colIndex++] = '';
                }
                $type = $cell->getAttribute('t');
                $value = '';
                $v = $cell->getElementsByTagName('v')->item(0);
                if ($v) {
                    $value = $type === 's'
                        ? ($sharedStrings[(int) $v->textContent] ?? '')
                        : (string) $v->textContent;
                }
                $cells[$colIndex++] = trim($value);
            }
            $rows[$rowIndex++] = $cells;
        }

        return array_values($rows);
    }

    private static function colIndexFromRef(string $ref): int
    {
        if (!preg_match('/^([A-Z]+)/', $ref, $m)) {
            return 0;
        }
        $letters = $m[1];
        $index = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return max(0, $index - 1);
    }
}
