<?php

declare(strict_types=1);

namespace App\Modules\Outils;

use App\Core\Auth;
use App\Core\View;
use App\Modules\Admin\AdminRepository;

final class OutilsController
{
    public static function index(): void
    {
        Auth::requireAuth();
        self::render($_GET['tab'] ?? 'calculateurs', null);
    }

    public static function calculate(): void
    {
        Auth::requireAuth();
        $calc = $_POST['calc'] ?? '';
        $result = self::runCalc($calc, $_POST);
        if ($result === null) {
            View::flashT('error', 'flash.outils_calc_invalid');
            View::redirect('/outils?tab=calculateurs');
        }
        self::render('calculateurs', ['type' => $calc, 'data' => $result]);
    }

    /** @param array<string, mixed>|null $calcResult */
    private static function render(string $tab, ?array $calcResult): void
    {
        View::render('outils/index', [
            'title' => 'Outils comptables & référentiel',
            'tab' => in_array($tab, ['calculateurs', 'referentiel', 'taux'], true) ? $tab : 'calculateurs',
            'calcResult' => $calcResult,
            'rates' => AdminRepository::rates(),
            'deadlines' => AdminRepository::deadlines(),
            'legal' => LegalReferenceService::categories(),
            'quickLinks' => LegalReferenceService::quickLinks(),
            'calendarNotes' => LegalReferenceService::fiscalCalendarNotes(),
        ]);
    }

    /** @param array<string, mixed> $post */
    private static function runCalc(string $calc, array $post): ?array
    {
        $float = static fn (string $key): float => (float) str_replace([' ', ','], ['', '.'], (string) ($post[$key] ?? '0'));

        return match ($calc) {
            'cnas' => AccountingToolsService::cnas(
                $float('assiette'),
                (string) ($post['secteur'] ?? 'BTP'),
                (string) ($post['cnas_type'] ?? 'CNAS_MENSUELLE')
            ),
            'cacobatph' => AccountingToolsService::cacobatph(
                $float('assiette'),
                max(0, (int) ($post['assurees'] ?? 0))
            ),
            'tva' => AccountingToolsService::tva(
                $float('montant'),
                (float) ($post['taux'] ?? 19),
                ($post['mode'] ?? 'ht') === 'ttc' ? 'ttc' : 'ht'
            ),
            'g50' => AccountingToolsService::g50(
                $float('ca_biens'),
                $float('ca_services'),
                ($post['irg_base'] ?? '') !== '' ? $float('irg_base') : null
            ),
            'g12' => AccountingToolsService::g12(
                $float('ca_biens'),
                $float('ca_services'),
                $float('ca_auto'),
                (string) ($post['secteur'] ?? 'SERVICES')
            ),
            'irg' => AccountingToolsService::irgSalaire($float('salaire_brut')),
            'amortissement' => AccountingToolsService::amortissementLineaire(
                $float('valeur'),
                max(1, (int) ($post['duree'] ?? 5))
            ),
            default => null,
        };
    }
}
