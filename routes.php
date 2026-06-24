<?php

declare(strict_types=1);

use App\Core\Router;
use App\Modules\Auth\AuthController;
use App\Modules\Alerts\AlertService;
use App\Modules\Clients\ClientController;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Admin\AdminController;
use App\Modules\Automation\AutomationController;
use App\Modules\Declarations\DeclarationController;
use App\Modules\AI\ChatController;
use App\Modules\Documents\DocumentController;
use App\Modules\Documents\GedController;
use App\Modules\Reports\ReportsController;
use App\Modules\Search\SearchController;
use App\Modules\Tasks\TaskController;
use App\Modules\Echeancier\EcheancierController;
use App\Modules\Entries\EntryController;
use App\Modules\Production\ProductionController;
use App\Core\Auth;
use App\Core\View;

$router = new Router();

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index']);
$router->post('/dashboard/automation', [AutomationController::class, 'runFromDashboard']);

$router->get('/clients', [ClientController::class, 'index']);
$router->get('/clients/search', [ClientController::class, 'searchApi']);
$router->get('/clients/create', [ClientController::class, 'createForm']);
$router->post('/clients', [ClientController::class, 'store']);
$router->get('/clients/{id}/dossier', [GedController::class, 'clientDossier']);
$router->post('/clients/{id}/dossier/upload', [GedController::class, 'upload']);
$router->get('/clients/{id}/edit', [ClientController::class, 'editForm']);
$router->post('/clients/{id}', [ClientController::class, 'update']);
$router->get('/clients/{id}', [ClientController::class, 'show']);

$router->post('/clients/{id}/notes', [ClientController::class, 'addNote']);
$router->post('/clients/{id}/notes/{noteId}/delete', [ClientController::class, 'deleteNote']);

$router->get('/tasks', [TaskController::class, 'index']);
$router->post('/tasks', [TaskController::class, 'store']);
$router->post('/tasks/{id}/complete', [TaskController::class, 'complete']);

$router->get('/search', [SearchController::class, 'index']);
$router->get('/rapports', [ReportsController::class, 'index']);
$router->get('/audit', [ReportsController::class, 'audit']);

$router->get('/ged', [GedController::class, 'index']);

$router->get('/assistant', [ChatController::class, 'index']);
$router->post('/assistant/chat', [ChatController::class, 'chat']);
$router->post('/assistant/new', [ChatController::class, 'newSession']);

$router->get('/echeancier', [EcheancierController::class, 'index']);

$router->get('/production', [ProductionController::class, 'index']);
$router->post('/production/process', [ProductionController::class, 'processMonth']);
$router->post('/production/approve-drafts', [ProductionController::class, 'approveDrafts']);
$router->get('/production/export-relances', [ProductionController::class, 'exportRelances']);

$router->get('/entries/payroll/import', [EntryController::class, 'payrollImportForm']);
$router->post('/entries/payroll/import', [EntryController::class, 'payrollImport']);
$router->get('/entries/payroll', [EntryController::class, 'payrollForm']);
$router->post('/entries/payroll', [EntryController::class, 'storePayroll']);
$router->get('/entries/sales', [EntryController::class, 'salesForm']);
$router->post('/entries/sales', [EntryController::class, 'storeSales']);

$router->post('/automation/batch-recalculate', [AutomationController::class, 'batchRecalculate']);
$router->get('/automation', [AutomationController::class, 'index']);
$router->post('/automation/run', [AutomationController::class, 'runCustom']);
$router->post('/automation/run-all', [AutomationController::class, 'runFull']);
$router->get('/automation/runs/{id}', [AutomationController::class, 'showRun']);
$router->post('/automation/generate-pdfs', [AutomationController::class, 'generatePdfs']);
$router->post('/automation/classify-documents', [AutomationController::class, 'classifyDocuments']);
$router->post('/automation/ai-relance', [AutomationController::class, 'aiRelance']);

$router->get('/admin', [AdminController::class, 'index']);
$router->post('/admin/settings', [AdminController::class, 'saveSettings']);
$router->post('/admin/users', [AdminController::class, 'storeUser']);
$router->post('/admin/users/{id}', [AdminController::class, 'updateUser']);
$router->post('/admin/rates', [AdminController::class, 'storeRate']);
$router->post('/admin/rates/{id}', [AdminController::class, 'updateRate']);
$router->post('/admin/deadlines/{id}', [AdminController::class, 'updateDeadline']);
$router->post('/admin/rules/{id}', [AdminController::class, 'toggleRule']);

$router->get('/declarations', [DeclarationController::class, 'index']);
$router->get('/declarations/{id}/print', [DeclarationController::class, 'print']);
$router->get('/declarations/{id}/generated', [DeclarationController::class, 'generatedPdf']);
$router->post('/declarations/{id}/generate-pdf', [DeclarationController::class, 'generatePdf']);
$router->post('/declarations/{id}/ai-review', [DeclarationController::class, 'aiReview']);
$router->get('/declarations/{id}/receipt', [DeclarationController::class, 'receipt']);
$router->get('/declarations/{id}', [DeclarationController::class, 'show']);
$router->post('/declarations/{id}', [DeclarationController::class, 'update']);
$router->post('/declarations/{id}/approve', [DeclarationController::class, 'approve']);
$router->post('/declarations/{id}/submit', [DeclarationController::class, 'submit']);

$router->get('/documents', [DocumentController::class, 'index']);
$router->post('/documents/upload', [DocumentController::class, 'upload']);
$router->get('/documents/{id}/download', [GedController::class, 'download']);
$router->post('/documents/{id}/ged', [GedController::class, 'update']);
$router->get('/documents/{id}', [DocumentController::class, 'show']);
$router->post('/documents/{id}/process', [DocumentController::class, 'process']);
$router->post('/documents/{id}/commit', [DocumentController::class, 'commit']);

$router->post('/alerts/{id}/read', function (int $id): void {
    Auth::requireAuth();
    AlertService::markRead($id);
    View::redirect('/');
});

return $router;
