<?php

declare(strict_types=1);

namespace App\Modules\Relances;

final class RelanceExportService
{
    /** @param list<array<string, mixed>> $rows */
    public static function csvContent(array $rows): string
    {
        $lines = ["Client;Téléphone;Email;Obligation;Période;Statut;Message"];
        foreach ($rows as $row) {
            $rel = RelanceService::linksFor($row);
            $message = str_replace(["\r", "\n", ';'], [' ', ' ', ','], $rel['message']);
            $lines[] = implode(';', [
                $row['raison_sociale'] ?? '',
                $row['contact_phone'] ?? '',
                $row['contact_email'] ?? '',
                $row['type_label'] ?? '',
                $row['period_label'] ?? '',
                $row['status_label'] ?? '',
                $message,
            ]);
        }
        return "\xEF\xBB\xBF" . implode("\n", $lines);
    }

    /** @param list<array<string, mixed>> $rows */
    public static function download(array $rows, string $filename): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo self::csvContent($rows);
        exit;
    }
}
