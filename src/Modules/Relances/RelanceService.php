<?php

declare(strict_types=1);

namespace App\Modules\Relances;

use App\Core\Database;

final class RelanceService
{
    /** @param array<string, mixed> $context */
    public static function buildMessage(array $context): string
    {
        $client = $context['raison_sociale'] ?? 'Client';
        $obligation = $context['type_label'] ?? '';
        $period = $context['period_label'] ?? '';
        $status = $context['status_label'] ?? '';
        $due = $context['due_label'] ?? '';

        if (($context['status'] ?? '') === 'missing_data') {
            return "Bonjour,\n\n"
                . "Cabinet comptable — relance pour {$client}.\n\n"
                . "Pour la déclaration {$obligation} ({$period}), nous avons besoin des éléments suivants :\n"
                . "- Fiche de paie / masse salariale du mois\n"
                . "- Effectif et mouvements (entrées/sorties)\n\n"
                . "Échéance de dépôt : {$due}.\n"
                . "Merci de nous transmettre ces documents dans les plus brefs délais.\n\n"
                . "Cordialement,\nVotre cabinet comptable";
        }

        return "Bonjour,\n\n"
            . "Cabinet comptable — relance {$client}.\n\n"
            . "Déclaration : {$obligation} — {$period}\n"
            . "Statut actuel : {$status}\n"
            . "Date limite de dépôt : {$due}.\n\n"
            . "Merci de régulariser la situation ou de nous contacter.\n\n"
            . "Cordialement,\nVotre cabinet comptable";
    }

    public static function whatsappUrl(?string $phone, string $message): ?string
    {
        $normalized = self::normalizePhone($phone);
        if ($normalized === null) {
            return null;
        }
        return 'https://wa.me/' . $normalized . '?text=' . rawurlencode($message);
    }

    public static function mailtoUrl(?string $email, string $subject, string $body): ?string
    {
        $email = trim((string) $email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return 'mailto:' . rawurlencode($email)
            . '?subject=' . rawurlencode($subject)
            . '&body=' . rawurlencode($body);
    }

    /** @param array<string, mixed> $row */
    public static function linksFor(array $row): array
    {
        $message = self::buildMessage($row);
        $subject = 'Relance — ' . ($row['type_label'] ?? 'Déclaration') . ' ' . ($row['period_label'] ?? '');

        return [
            'message' => $message,
            'whatsapp' => self::whatsappUrl($row['contact_phone'] ?? null, $message),
            'email' => self::mailtoUrl($row['contact_email'] ?? null, $subject, $message),
            'has_contact' => !empty($row['contact_phone']) || !empty($row['contact_email']),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function pendingForCabinet(int $cabinetId, int $limit = 50): array
    {
        $items = [];
        foreach (\App\Modules\Automation\BatchService::clientsToChase($cabinetId, $limit) as $ob) {
            $client = Database::fetchOne(
                'SELECT contact_email, contact_phone, contact_name FROM clients WHERE id = ?',
                [$ob['client_id']]
            );
            $row = array_merge($ob, [
                'contact_email' => $client['contact_email'] ?? null,
                'contact_phone' => $client['contact_phone'] ?? null,
                'contact_name' => $client['contact_name'] ?? null,
            ]);
            $row['relance'] = self::linksFor($row);
            $items[] = $row;
        }
        return $items;
    }

    private static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === null || $digits === '') {
            return null;
        }
        if (str_starts_with($digits, '213')) {
            return $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '213' . substr($digits, 1);
        }
        if (strlen($digits) === 9) {
            return '213' . $digits;
        }
        return strlen($digits) >= 10 ? $digits : null;
    }
}
