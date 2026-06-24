<?php

declare(strict_types=1);

namespace App\Modules\Entries;

use App\Core\Auth;
use App\Core\SimpleSpreadsheetReader;
use App\Modules\Clients\ClientRepository;

final class PayrollImportService
{
    /** @return array{imported: int, skipped: int, errors: list<string>} */
    public static function importFile(string $filePath, array $defaults = []): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            $allRows = SimpleSpreadsheetReader::rows($filePath);
            if ($allRows === []) {
                throw new \RuntimeException('Fichier vide.');
            }
            $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $allRows[0]);
            $map = self::columnMap($header);
            $imported = 0;
            $skipped = 0;
            $errors = [];
            for ($i = 1, $line = 2; $i < count($allRows); $i++, $line++) {
                $row = $allRows[$i];
                if (count(array_filter($row, static fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                try {
                    $data = self::parseRow($row, $map, $defaults);
                    $clientId = self::resolveClientId($data);
                    if ($clientId <= 0) {
                        throw new \RuntimeException('client introuvable');
                    }
                    EntryRepository::savePayroll($clientId, [
                        'period_year' => $data['period_year'],
                        'period_month' => $data['period_month'],
                        'masse_salariale' => $data['masse_salariale'],
                        'effectif' => $data['effectif'],
                        'entrees' => $data['entrees'],
                        'sorties' => $data['sorties'],
                        'nombre_assurees' => $data['nombre_assurees'],
                        'source' => 'import',
                        'notes' => $data['notes'],
                    ]);
                    $imported++;
                } catch (\Throwable $e) {
                    $skipped++;
                    $errors[] = "Ligne {$line}: " . $e->getMessage();
                }
            }
            if ($imported > 0) {
                \App\Modules\Automation\BatchService::recalculateCabinet(Auth::cabinetId());
            }
            return compact('imported', 'skipped', 'errors');
        }

        return self::importCsv($filePath, $defaults);
    }

    /** @return array{imported: int, skipped: int, errors: list<string>} */
    public static function importCsv(string $filePath, array $defaults = []): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Impossible de lire le fichier.');
        }

        $firstLine = fgets($handle) ?: '';
        rewind($handle);
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);
            throw new \RuntimeException('Fichier CSV vide.');
        }
        $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $header);

        $map = self::columnMap($header);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($row, static fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            try {
                $data = self::parseRow($row, $map, $defaults);
                $clientId = self::resolveClientId($data);
                if ($clientId <= 0) {
                    throw new \RuntimeException('client introuvable');
                }
                EntryRepository::savePayroll($clientId, [
                    'period_year' => $data['period_year'],
                    'period_month' => $data['period_month'],
                    'masse_salariale' => $data['masse_salariale'],
                    'effectif' => $data['effectif'],
                    'entrees' => $data['entrees'],
                    'sorties' => $data['sorties'],
                    'nombre_assurees' => $data['nombre_assurees'],
                    'source' => 'import',
                    'notes' => $data['notes'],
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Ligne {$line}: " . $e->getMessage();
            }
        }

        fclose($handle);

        if ($imported > 0) {
            \App\Modules\Automation\BatchService::recalculateCabinet(Auth::cabinetId());
        }

        return compact('imported', 'skipped', 'errors');
    }

    /** @param list<string> $header */
    private static function columnMap(array $header): array
    {
        $aliases = [
            'client_id' => ['client_id', 'id_client', 'client'],
            'raison_sociale' => ['raison_sociale', 'client_nom', 'nom', 'societe'],
            'period_year' => ['period_year', 'annee', 'year', 'annee_periode'],
            'period_month' => ['period_month', 'mois', 'month'],
            'masse_salariale' => ['masse_salariale', 'masse', 'salaire', 'assiette'],
            'effectif' => ['effectif', 'nb_salaries', 'salaries'],
            'nombre_assurees' => ['nombre_assurees', 'assures', 'nb_assures'],
            'entrees' => ['entrees', 'embauches'],
            'sorties' => ['sorties', 'departs'],
            'notes' => ['notes', 'commentaire'],
        ];

        $map = [];
        foreach ($aliases as $field => $names) {
            foreach ($names as $name) {
                $idx = array_search($name, $header, true);
                if ($idx !== false) {
                    $map[$field] = $idx;
                    break;
                }
            }
        }

        return $map;
    }

    /** @param list<string|null> $row */
    private static function parseRow(array $row, array $map, array $defaults = []): array
    {
        $get = static function (string $field, $default = '') use ($row, $map) {
            if (!isset($map[$field])) {
                return $default;
            }
            return trim((string) ($row[$map[$field]] ?? ''));
        };

        $masse = (float) str_replace([' ', ','], ['', '.'], $get('masse_salariale', '0'));
        if ($masse <= 0) {
            throw new \RuntimeException('masse salariale invalide');
        }

        $year = (int) ($get('period_year') ?: ($defaults['year'] ?? date('Y')));
        $month = (int) ($get('period_month') ?: ($defaults['month'] ?? date('n')));
        $effectif = (int) ($get('effectif', '1') ?: 1);

        return [
            'client_id' => $get('client_id'),
            'raison_sociale' => $get('raison_sociale'),
            'period_year' => $year,
            'period_month' => max(1, min(12, $month)),
            'masse_salariale' => $masse,
            'effectif' => $effectif,
            'entrees' => (int) ($get('entrees', '0') ?: 0),
            'sorties' => (int) ($get('sorties', '0') ?: 0),
            'nombre_assurees' => (int) ($get('nombre_assurees', (string) $effectif) ?: $effectif),
            'notes' => $get('notes', 'Import CSV'),
        ];
    }

    private static function resolveClientId(array $data): int
    {
        if ($data['client_id'] !== '' && ctype_digit($data['client_id'])) {
            $id = (int) $data['client_id'];
            if (ClientRepository::find($id)) {
                return $id;
            }
        }
        if ($data['raison_sociale'] !== '') {
            foreach (ClientRepository::allForCabinet() as $client) {
                if (strcasecmp($client['raison_sociale'], $data['raison_sociale']) === 0) {
                    return (int) $client['id'];
                }
            }
        }
        return 0;
    }

    public static function sampleCsv(?int $year = null, ?int $month = null): string
    {
        $year = $year ?? (int) date('Y');
        $month = $month ?? max(1, (int) date('n') - 1);
        return "client_id;raison_sociale;period_year;period_month;masse_salariale;effectif;nombre_assurees;entrees;sorties\n"
            . "1;BOUALAM MOHAMED;{$year};{$month};173781.80;7;22;1;1\n";
    }
}
