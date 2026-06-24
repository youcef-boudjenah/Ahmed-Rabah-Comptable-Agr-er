<?php

declare(strict_types=1);

namespace App\Modules\Clients;

use App\Core\Auth;
use App\Core\AuditLog;
use App\Core\Database;
use App\Core\Encryption;
use App\Modules\Alerts\AlertService;

final class ClientRepository
{
    public static function allForCabinet(): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM clients WHERE cabinet_id = ? ORDER BY raison_sociale',
            [Auth::cabinetId()]
        );
        return array_map([self::class, 'decryptRow'], $rows);
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetchOne(
            'SELECT * FROM clients WHERE id = ? AND cabinet_id = ?',
            [$id, Auth::cabinetId()]
        );
        return $row ? self::decryptRow($row) : null;
    }

    /** Lightweight list for dropdowns when client already selected */
    public static function findLight(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT id, raison_sociale, secteur, wilaya FROM clients WHERE id = ? AND cabinet_id = ? AND is_active = 1',
            [$id, Auth::cabinetId()]
        );
    }

    public static function create(array $data): int
    {
        $id = Database::insert(
            'INSERT INTO clients (cabinet_id, raison_sociale, nif_encrypted, nin_encrypted, numero_cotisant, secteur, regime_fiscal, cnas_regime, wilaya, adresse, activite, contact_email, contact_phone, contact_name)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                Auth::cabinetId(),
                $data['raison_sociale'],
                Encryption::encrypt($data['nif'] ?? null),
                Encryption::encrypt($data['nin'] ?? null),
                $data['numero_cotisant'] ?? null,
                $data['secteur'],
                $data['regime_fiscal'],
                $data['cnas_regime'],
                $data['wilaya'] ?? null,
                $data['adresse'] ?? null,
                $data['activite'] ?? null,
                $data['contact_email'] ?? null,
                $data['contact_phone'] ?? null,
                $data['contact_name'] ?? null,
            ]
        );
        AuditLog::write('create', 'clients', $id);
        AlertService::syncForClient($id);
        \App\Modules\Documents\ClientFolderService::ensure($id);
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE clients SET raison_sociale=?, nif_encrypted=?, nin_encrypted=?, numero_cotisant=?, secteur=?, regime_fiscal=?, cnas_regime=?, wilaya=?, adresse=?, activite=?, contact_email=?, contact_phone=?, contact_name=?, updated_at=NOW()
             WHERE id=? AND cabinet_id=?',
            [
                $data['raison_sociale'],
                Encryption::encrypt($data['nif'] ?? null),
                Encryption::encrypt($data['nin'] ?? null),
                $data['numero_cotisant'] ?? null,
                $data['secteur'],
                $data['regime_fiscal'],
                $data['cnas_regime'],
                $data['wilaya'] ?? null,
                $data['adresse'] ?? null,
                $data['activite'] ?? null,
                $data['contact_email'] ?? null,
                $data['contact_phone'] ?? null,
                $data['contact_name'] ?? null,
                $id,
                Auth::cabinetId(),
            ]
        );
        AuditLog::write('update', 'clients', $id);
        AlertService::syncForClient($id);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM clients WHERE id = ? AND cabinet_id = ?', [$id, Auth::cabinetId()]);
        AuditLog::write('delete', 'clients', $id);
    }

    private static function decryptRow(array $row): array
    {
        $row['nif'] = Encryption::decrypt($row['nif_encrypted'] ?? null);
        $row['nin'] = Encryption::decrypt($row['nin_encrypted'] ?? null);
        return $row;
    }
}
