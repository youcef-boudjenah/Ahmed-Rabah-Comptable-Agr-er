<?php

declare(strict_types=1);

namespace App\Modules\Clients;

use App\Core\Auth;
use App\Core\Database;

final class ClientListService
{
    private const PER_PAGE_OPTIONS = [25, 50, 100];

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int} */
    public static function paginated(array $filters = []): array
    {
        $cabinetId = Auth::cabinetId();
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = (int) ($filters['per_page'] ?? 50);
        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 50;
        }

        [$whereSql, $params] = self::buildWhere($cabinetId, $filters);

        $total = (int) Database::fetchOne(
            "SELECT COUNT(*) AS c FROM clients c WHERE {$whereSql}",
            $params
        )['c'];

        $order = self::orderClause($filters['sort'] ?? 'name');
        $offset = ($page - 1) * $perPage;

        $rows = Database::fetchAll(
            "SELECT c.id, c.raison_sociale, c.secteur, c.wilaya, c.cnas_regime,
                    c.numero_cotisant, c.regime_fiscal, c.activite,
                    (SELECT COUNT(*) FROM alerts a WHERE a.client_id = c.id AND a.is_read = 0 AND a.severity = 'critical') AS critical_count,
                    (SELECT COUNT(*) FROM alerts a WHERE a.client_id = c.id AND a.is_read = 0 AND a.severity = 'warning') AS warning_count,
                    (SELECT COUNT(*) FROM declarations d WHERE d.client_id = c.id AND d.status = 'DRAFT_CALCULATED') AS draft_count,
                    (SELECT COUNT(*) FROM documents doc WHERE doc.client_id = c.id) AS doc_count
             FROM clients c
             WHERE {$whereSql}
             ORDER BY {$order}
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        foreach ($rows as &$row) {
            $row['issue_score'] = (int) $row['critical_count'] * 3 + (int) $row['warning_count'] + (int) $row['draft_count'];
            $row['status_level'] = self::statusLevel($row);
        }
        unset($row);

        return [
            'items' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /** @return array{total: int, critical: int, warning: int, secteurs: list<string>} */
    public static function cabinetStats(): array
    {
        $cabinetId = Auth::cabinetId();
        $total = (int) Database::fetchOne(
            'SELECT COUNT(*) AS c FROM clients WHERE cabinet_id = ? AND is_active = 1',
            [$cabinetId]
        )['c'];
        $critical = (int) Database::fetchOne(
            "SELECT COUNT(DISTINCT a.client_id) AS c FROM alerts a
             WHERE a.cabinet_id = ? AND a.is_read = 0 AND a.severity = 'critical'",
            [$cabinetId]
        )['c'];
        $warning = (int) Database::fetchOne(
            "SELECT COUNT(DISTINCT a.client_id) AS c FROM alerts a
             WHERE a.cabinet_id = ? AND a.is_read = 0 AND a.severity = 'warning'",
            [$cabinetId]
        )['c'];
        $secteurs = Database::fetchAll(
            'SELECT DISTINCT secteur FROM clients WHERE cabinet_id = ? AND is_active = 1 ORDER BY secteur',
            [$cabinetId]
        );

        return [
            'total' => $total,
            'critical' => $critical,
            'warning' => $warning,
            'secteurs' => array_column($secteurs, 'secteur'),
        ];
    }

    /** @return list<array{id: int, raison_sociale: string, secteur: string, wilaya: string|null}> */
    public static function search(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if (strlen($query) < 1) {
            return [];
        }
        $cabinetId = Auth::cabinetId();
        $limit = max(1, min(50, $limit));
        $like = '%' . $query . '%';

        return Database::fetchAll(
            "SELECT id, raison_sociale, secteur, wilaya, numero_cotisant
             FROM clients
             WHERE cabinet_id = ? AND is_active = 1
             AND (raison_sociale LIKE ? OR wilaya LIKE ? OR numero_cotisant LIKE ? OR activite LIKE ?)
             ORDER BY raison_sociale
             LIMIT {$limit}",
            [$cabinetId, $like, $like, $like, $like]
        );
    }

    public static function perPageOptions(): array
    {
        return self::PER_PAGE_OPTIONS;
    }

    /** @return array{0: string, 1: list<mixed>} */
    private static function buildWhere(int $cabinetId, array $filters): array
    {
        $where = ['c.cabinet_id = ?', 'c.is_active = 1'];
        $params = [$cabinetId];

        $q = trim($filters['q'] ?? '');
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(c.raison_sociale LIKE ? OR c.wilaya LIKE ? OR c.numero_cotisant LIKE ? OR c.activite LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }

        if (!empty($filters['secteur'])) {
            $where[] = 'c.secteur = ?';
            $params[] = $filters['secteur'];
        }

        if (!empty($filters['wilaya'])) {
            $where[] = 'c.wilaya LIKE ?';
            $params[] = '%' . $filters['wilaya'] . '%';
        }

        $status = $filters['status'] ?? '';
        if ($status === 'critical') {
            $where[] = "EXISTS (SELECT 1 FROM alerts a WHERE a.client_id = c.id AND a.is_read = 0 AND a.severity = 'critical')";
        } elseif ($status === 'warning') {
            $where[] = "EXISTS (SELECT 1 FROM alerts a WHERE a.client_id = c.id AND a.is_read = 0 AND a.severity IN ('warning','critical'))";
        } elseif ($status === 'drafts') {
            $where[] = "EXISTS (SELECT 1 FROM declarations d WHERE d.client_id = c.id AND d.status = 'DRAFT_CALCULATED')";
        } elseif ($status === 'ok') {
            $where[] = "NOT EXISTS (SELECT 1 FROM alerts a WHERE a.client_id = c.id AND a.is_read = 0 AND a.severity IN ('warning','critical'))";
        }

        return [implode(' AND ', $where), $params];
    }

    private static function orderClause(string $sort): string
    {
        return match ($sort) {
            'secteur' => 'c.secteur ASC, c.raison_sociale ASC',
            'wilaya' => 'c.wilaya ASC, c.raison_sociale ASC',
            'issues' => 'critical_count DESC, warning_count DESC, c.raison_sociale ASC',
            'drafts' => 'draft_count DESC, c.raison_sociale ASC',
            default => 'c.raison_sociale ASC',
        };
    }

    /** @param array<string, mixed> $row */
    private static function statusLevel(array $row): string
    {
        if ((int) ($row['critical_count'] ?? 0) > 0) {
            return 'critical';
        }
        if ((int) ($row['warning_count'] ?? 0) > 0 || (int) ($row['draft_count'] ?? 0) > 0) {
            return 'warning';
        }
        return 'ok';
    }
}
