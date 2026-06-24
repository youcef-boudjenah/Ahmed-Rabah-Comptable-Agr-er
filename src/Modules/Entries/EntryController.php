<?php

declare(strict_types=1);

namespace App\Modules\Entries;

use App\Core\Auth;
use App\Core\View;
use App\Modules\Automation\WorkflowService;
use App\Modules\Entries\PayrollImportService;

final class EntryController
{
    public static function payrollForm(?int $clientId = null): void
    {
        Auth::requireAuth();
        $selected = $clientId ?? (isset($_GET['client']) ? (int) $_GET['client'] : null);
        View::render('entries/payroll', [
            'title' => 'Saisie paie',
            'selectedClientId' => $selected,
        ]);
    }

    public static function storePayroll(): void
    {
        Auth::requireAuth();
        $clientId = (int) ($_POST['client_id'] ?? 0);
        EntryRepository::savePayroll($clientId, [
            'period_year' => (int) $_POST['period_year'],
            'period_month' => (int) $_POST['period_month'],
            'masse_salariale' => (float) str_replace([' ', ','], ['', '.'], $_POST['masse_salariale'] ?? '0'),
            'effectif' => (int) ($_POST['effectif'] ?? 0),
            'entrees' => (int) ($_POST['entrees'] ?? 0),
            'sorties' => (int) ($_POST['sorties'] ?? 0),
            'nombre_assurees' => (int) ($_POST['nombre_assurees'] ?? $_POST['effectif'] ?? 0),
            'source' => 'manual',
            'notes' => trim($_POST['notes'] ?? ''),
        ]);
        $declId = WorkflowService::afterPayrollSaved($clientId);
        View::flash('success', 'Paie enregistrée. Déclarations CNAS/CACOBATPH recalculées.');
        View::redirect($declId ? '/declarations/' . $declId : '/declarations?status=DRAFT_CALCULATED');
    }

    public static function payrollImportForm(): void
    {
        Auth::requireAuth();
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $month = isset($_GET['month']) ? (int) $_GET['month'] : max(1, (int) date('n') - 1);
        View::render('entries/payroll_import', [
            'title' => 'Import paie (Excel / CSV)',
            'sample' => PayrollImportService::sampleCsv($year, $month),
            'defaultYear' => $year,
            'defaultMonth' => $month,
            'redirect' => $_GET['redirect'] ?? '/production',
        ]);
    }

    public static function payrollImport(): void
    {
        Auth::requireAuth();
        $field = isset($_FILES['file']) ? 'file' : 'csv';
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            View::flash('error', 'Fichier Excel (.xlsx) ou CSV requis.');
            View::redirect('/entries/payroll/import');
        }
        $defaults = [
            'year' => (int) ($_POST['default_year'] ?? date('Y')),
            'month' => (int) ($_POST['default_month'] ?? date('n')),
        ];
        $result = PayrollImportService::importFile($_FILES[$field]['tmp_name'], $defaults);
        $msg = sprintf('%d ligne(s) importée(s), %d ignorée(s).', $result['imported'], $result['skipped']);
        if (!empty($result['errors'])) {
            $msg .= ' ' . implode(' | ', array_slice($result['errors'], 0, 3));
        }
        View::flash($result['imported'] > 0 ? 'success' : 'error', $msg);
        $redirect = $_POST['redirect'] ?? '/declarations?status=DRAFT_CALCULATED';
        if ($result['imported'] > 0 && str_starts_with($redirect, '/production')) {
            $sep = str_contains($redirect, '?') ? '&' : '?';
            $redirect .= $sep . 'year=' . $defaults['year'] . '&month=' . $defaults['month'];
        }
        View::redirect($redirect);
    }

    public static function salesForm(?int $clientId = null): void
    {
        Auth::requireAuth();
        $selected = $clientId ?? (isset($_GET['client']) ? (int) $_GET['client'] : null);
        View::render('entries/sales', [
            'title' => 'Saisie ventes / CA',
            'selectedClientId' => $selected,
        ]);
    }

    public static function storeSales(): void
    {
        Auth::requireAuth();
        $clientId = (int) ($_POST['client_id'] ?? 0);
        EntryRepository::saveSales($clientId, [
            'period_year' => (int) $_POST['period_year'],
            'period_month' => $_POST['period_month'] !== '' ? (int) $_POST['period_month'] : null,
            'ca_biens' => (float) str_replace([' ', ','], ['', '.'], $_POST['ca_biens'] ?? '0'),
            'ca_services' => (float) str_replace([' ', ','], ['', '.'], $_POST['ca_services'] ?? '0'),
            'ca_auto_entrepreneur' => (float) str_replace([' ', ','], ['', '.'], $_POST['ca_auto_entrepreneur'] ?? '0'),
            'irg_acompte_base' => $_POST['irg_acompte_base'] !== '' ? (float) str_replace([' ', ','], ['', '.'], $_POST['irg_acompte_base']) : null,
            'source' => 'manual',
            'notes' => trim($_POST['notes'] ?? ''),
        ]);
        $declId = WorkflowService::afterSalesSaved($clientId);
        View::flash('success', 'Ventes enregistrées. Déclarations G50/G12 recalculées.');
        View::redirect($declId ? '/declarations/' . $declId : '/declarations?status=DRAFT_CALCULATED');
    }
}
