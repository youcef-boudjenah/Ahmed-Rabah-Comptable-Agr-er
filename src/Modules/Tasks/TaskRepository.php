<?php

declare(strict_types=1);

namespace App\Modules\Tasks;

use App\Core\Database;

final class TaskRepository
{
  /** @return list<array<string, mixed>> */
  public static function openForCabinet(int $cabinetId, int $limit = 15): array
  {
    $limit = max(1, min(100, $limit));
    return Database::fetchAll(
      "SELECT t.*, c.raison_sociale, u.name AS assignee
       FROM cabinet_tasks t
       LEFT JOIN clients c ON c.id = t.client_id
       JOIN users u ON u.id = t.user_id
       WHERE t.cabinet_id = ? AND t.is_done = 0
       ORDER BY FIELD(t.priority, 'high', 'normal', 'low'), t.due_date IS NULL, t.due_date ASC, t.created_at DESC
       LIMIT {$limit}",
      [$cabinetId]
    );
  }

  public static function create(int $cabinetId, int $userId, string $title, ?int $clientId, ?string $dueDate, string $priority): int
  {
    Database::insert(
      'INSERT INTO cabinet_tasks (cabinet_id, client_id, user_id, title, due_date, priority) VALUES (?, ?, ?, ?, ?, ?)',
      [$cabinetId, $clientId, $userId, $title, $dueDate ?: null, $priority]
    );
    return (int) Database::connection()->lastInsertId();
  }

  public static function markDone(int $id, int $cabinetId): void
  {
    Database::query(
      'UPDATE cabinet_tasks SET is_done = 1, completed_at = NOW() WHERE id = ? AND cabinet_id = ?',
      [$id, $cabinetId]
    );
  }

  /** @return list<array<string, mixed>> */
  public static function listForCabinet(int $cabinetId, string $filter = 'open'): array
  {
    $where = 't.cabinet_id = ?';
    $params = [$cabinetId];
    if ($filter === 'open') {
      $where .= ' AND t.is_done = 0';
    } elseif ($filter === 'done') {
      $where .= ' AND t.is_done = 1';
    } elseif ($filter === 'high') {
      $where .= ' AND t.is_done = 0 AND t.priority = \'high\'';
    }

    return Database::fetchAll(
      "SELECT t.*, c.raison_sociale, u.name AS assignee
       FROM cabinet_tasks t
       LEFT JOIN clients c ON c.id = t.client_id
       JOIN users u ON u.id = t.user_id
       WHERE {$where}
       ORDER BY t.is_done ASC, FIELD(t.priority, 'high', 'normal', 'low'), t.due_date IS NULL, t.due_date ASC, t.created_at DESC
       LIMIT 100",
      $params
    );
  }

  /** @return array{open: int, high: int, done: int} */
  public static function counts(int $cabinetId): array
  {
    $open = Database::fetchOne('SELECT COUNT(*) AS c FROM cabinet_tasks WHERE cabinet_id = ? AND is_done = 0', [$cabinetId]);
    $high = Database::fetchOne('SELECT COUNT(*) AS c FROM cabinet_tasks WHERE cabinet_id = ? AND is_done = 0 AND priority = \'high\'', [$cabinetId]);
    $done = Database::fetchOne('SELECT COUNT(*) AS c FROM cabinet_tasks WHERE cabinet_id = ? AND is_done = 1', [$cabinetId]);
    return [
      'open' => (int) ($open['c'] ?? 0),
      'high' => (int) ($high['c'] ?? 0),
      'done' => (int) ($done['c'] ?? 0),
    ];
  }
}
