<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Auth;
use App\Core\Database;

final class AdminRepository
{
    public static function users(): array
    {
        return Database::fetchAll(
            'SELECT id, name, email, role, created_at FROM users WHERE cabinet_id = ? ORDER BY name',
            [Auth::cabinetId()]
        );
    }

    public static function rates(): array
    {
        return Database::fetchAll(
            'SELECT * FROM cotisation_rate_tables ORDER BY declaration_type, code, secteur'
        );
    }

    public static function deadlines(): array
    {
        return Database::fetchAll('SELECT * FROM deadline_rules ORDER BY declaration_type');
    }

    public static function automationRules(): array
    {
        return Database::fetchAll('SELECT * FROM automation_rules ORDER BY id');
    }

    /** @return array<string, mixed> */
    public static function systemStats(int $cabinetId): array
    {
        return [
            'clients' => (int) Database::fetchOne('SELECT COUNT(*) AS c FROM clients WHERE cabinet_id = ? AND is_active = 1', [$cabinetId])['c'],
            'declarations' => (int) Database::fetchOne(
                'SELECT COUNT(*) AS c FROM declarations d JOIN clients c ON c.id = d.client_id WHERE c.cabinet_id = ?',
                [$cabinetId]
            )['c'],
            'documents' => (int) Database::fetchOne('SELECT COUNT(*) AS c FROM documents WHERE cabinet_id = ?', [$cabinetId])['c'],
            'queue_pending' => (int) Database::fetchOne("SELECT COUNT(*) AS c FROM job_queue WHERE status = 'pending'")['c'],
            'tasks_open' => (int) Database::fetchOne('SELECT COUNT(*) AS c FROM cabinet_tasks WHERE cabinet_id = ? AND is_done = 0', [$cabinetId])['c'],
        ];
    }

    public static function createUser(string $name, string $email, string $password, string $role): int
    {
        return Database::insert(
            'INSERT INTO users (cabinet_id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)',
            [Auth::cabinetId(), $name, $email, password_hash($password, PASSWORD_DEFAULT), $role]
        );
    }

    public static function updateUser(int $id, string $name, string $role, ?string $newPassword = null): void
    {
        if ($newPassword !== null && $newPassword !== '') {
            Database::query(
                'UPDATE users SET name = ?, role = ?, password_hash = ? WHERE id = ? AND cabinet_id = ?',
                [$name, $role, password_hash($newPassword, PASSWORD_DEFAULT), $id, Auth::cabinetId()]
            );
            return;
        }
        Database::query(
            'UPDATE users SET name = ?, role = ? WHERE id = ? AND cabinet_id = ?',
            [$name, $role, $id, Auth::cabinetId()]
        );
    }

    public static function storeRate(array $data): int
    {
        return Database::insert(
            'INSERT INTO cotisation_rate_tables (code, label, taux, secteur, declaration_type, valid_from, valid_to)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['code'],
                $data['label'],
                $data['taux'],
                $data['secteur'] ?: null,
                $data['declaration_type'],
                $data['valid_from'],
                $data['valid_to'] ?: null,
            ]
        );
    }

    public static function updateRate(int $id, array $data): void
    {
        Database::query(
            'UPDATE cotisation_rate_tables SET code=?, label=?, taux=?, secteur=?, declaration_type=?, valid_from=?, valid_to=? WHERE id=?',
            [
                $data['code'],
                $data['label'],
                $data['taux'],
                $data['secteur'] ?: null,
                $data['declaration_type'],
                $data['valid_from'],
                $data['valid_to'] ?: null,
                $id,
            ]
        );
    }

    public static function updateDeadline(int $id, int $dueDay, ?int $dueMonth, string $label): void
    {
        Database::query(
            'UPDATE deadline_rules SET due_day = ?, due_month = ?, label_fr = ? WHERE id = ?',
            [$dueDay, $dueMonth, $label, $id]
        );
    }

    public static function toggleAutomationRule(int $id, bool $active): void
    {
        Database::query('UPDATE automation_rules SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
    }

    public static function updateCabinetName(string $name): void
    {
        Database::query('UPDATE cabinets SET name = ? WHERE id = ?', [$name, Auth::cabinetId()]);
        if (Auth::check()) {
            $_SESSION['user']['cabinet_name'] = $name;
        }
    }
}
