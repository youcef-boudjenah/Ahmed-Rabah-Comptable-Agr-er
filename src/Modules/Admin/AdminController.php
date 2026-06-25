<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Modules\Automation\AutomationPipeline;

final class AdminController
{
    public static function index(): void
    {
        Auth::requireAdmin();
        $tab = $_GET['tab'] ?? 'settings';
        $cabinetId = Auth::cabinetId();
        $cabinet = Database::fetchOne('SELECT * FROM cabinets WHERE id = ?', [$cabinetId]);

        View::render('admin/index', [
            'title' => 'Paramètres & contrôle',
            'tab' => $tab,
            'settings' => SettingsService::all(),
            'users' => AdminRepository::users(),
            'rates' => AdminRepository::rates(),
            'deadlines' => AdminRepository::deadlines(),
            'automationRules' => AdminRepository::automationRules(),
            'systemStats' => AdminRepository::systemStats($cabinetId),
            'recentRuns' => AutomationPipeline::recentRuns($cabinetId, 5),
            'cabinet' => $cabinet,
            'config' => require ROOT_PATH . '/config/app.php',
        ]);
    }

    public static function saveSettings(): void
    {
        Auth::requireAdmin();
        $bool = static fn (string $k) => isset($_POST[$k]);

        SettingsService::update([
            'auto_ai_classify' => $bool('auto_ai_classify'),
            'auto_ai_review_pipeline' => $bool('auto_ai_review_pipeline'),
            'auto_pdf_on_approve' => $bool('auto_pdf_on_approve'),
            'auto_sync_tasks' => $bool('auto_sync_tasks'),
            'auto_ocr_on_upload' => $bool('auto_ocr_on_upload'),
            'pipeline_with_ai_default' => $bool('pipeline_with_ai_default'),
            'collaborateur_can_approve' => $bool('collaborateur_can_approve'),
            'collaborateur_can_submit' => $bool('collaborateur_can_submit'),
            'alert_days_before' => max(1, min(60, (int) ($_POST['alert_days_before'] ?? 7))),
        ]);

        if (trim($_POST['cabinet_name'] ?? '') !== '') {
            AdminRepository::updateCabinetName(trim($_POST['cabinet_name']));
        }

        View::flashT('success', 'flash.admin_settings_saved');
        View::redirect('/admin?tab=settings');
    }

    public static function storeUser(): void
    {
        Auth::requireAdmin();
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['admin', 'collaborateur'], true) ? $_POST['role'] : 'collaborateur';

        if ($name === '' || $email === '' || strlen($password) < 6) {
            View::flashT('error', 'flash.admin_user_fields');
            View::redirect('/admin?tab=users');
        }

        $exists = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) {
            View::flashT('error', 'flash.admin_email_taken');
            View::redirect('/admin?tab=users');
        }

        AdminRepository::createUser($name, $email, $password, $role);
        View::flashT('success', 'flash.admin_user_created');
        View::redirect('/admin?tab=users');
    }

    public static function updateUser(int $id): void
    {
        Auth::requireAdmin();
        if ($id === Auth::id() && ($_POST['role'] ?? '') !== 'admin') {
            View::flashT('error', 'flash.admin_cannot_demote');
            View::redirect('/admin?tab=users');
        }
        AdminRepository::updateUser(
            $id,
            trim($_POST['name'] ?? ''),
            in_array($_POST['role'] ?? '', ['admin', 'collaborateur'], true) ? $_POST['role'] : 'collaborateur',
            $_POST['password'] ?? null
        );
        View::flashT('success', 'flash.admin_user_updated');
        View::redirect('/admin?tab=users');
    }

    public static function storeRate(): void
    {
        Auth::requireAdmin();
        AdminRepository::storeRate([
            'code' => trim($_POST['code'] ?? ''),
            'label' => trim($_POST['label'] ?? ''),
            'taux' => (float) str_replace(',', '.', $_POST['taux'] ?? '0'),
            'secteur' => $_POST['secteur'] ?? '',
            'declaration_type' => $_POST['declaration_type'] ?? '',
            'valid_from' => $_POST['valid_from'] ?? date('Y-m-d'),
            'valid_to' => $_POST['valid_to'] ?? '',
        ]);
        View::flashT('success', 'flash.admin_rate_added');
        View::redirect('/admin?tab=rates');
    }

    public static function updateRate(int $id): void
    {
        Auth::requireAdmin();
        AdminRepository::updateRate($id, [
            'code' => trim($_POST['code'] ?? ''),
            'label' => trim($_POST['label'] ?? ''),
            'taux' => (float) str_replace(',', '.', $_POST['taux'] ?? '0'),
            'secteur' => $_POST['secteur'] ?? '',
            'declaration_type' => $_POST['declaration_type'] ?? '',
            'valid_from' => $_POST['valid_from'] ?? date('Y-m-d'),
            'valid_to' => $_POST['valid_to'] ?? '',
        ]);
        View::flashT('success', 'flash.admin_rate_updated');
        View::redirect('/admin?tab=rates');
    }

    public static function updateDeadline(int $id): void
    {
        Auth::requireAdmin();
        AdminRepository::updateDeadline(
            $id,
            max(1, min(31, (int) ($_POST['due_day'] ?? 20))),
            $_POST['due_month'] !== '' ? (int) $_POST['due_month'] : null,
            trim($_POST['label_fr'] ?? '')
        );
        View::flashT('success', 'flash.admin_deadline_updated');
        View::redirect('/admin?tab=deadlines');
    }

    public static function toggleRule(int $id): void
    {
        Auth::requireAdmin();
        $active = isset($_POST['is_active']);
        AdminRepository::toggleAutomationRule($id, $active);
        View::flashT('success', 'flash.admin_rule_updated');
        View::redirect('/admin?tab=automation');
    }
}
