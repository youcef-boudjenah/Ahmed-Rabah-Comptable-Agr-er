<?php

declare(strict_types=1);

namespace App\Modules\Tasks;

use App\Core\Auth;
use App\Core\View;

final class TaskController
{
  public static function index(): void
  {
    Auth::requireAuth();
    $filter = $_GET['filter'] ?? 'open';
    View::render('tasks/index', [
      'title' => 'Tâches cabinet',
      'tasks' => TaskRepository::listForCabinet(Auth::cabinetId(), $filter),
      'filter' => $filter,
      'counts' => TaskRepository::counts(Auth::cabinetId()),
    ]);
  }

  public static function store(): void
  {
    Auth::requireAuth();
    $title = trim($_POST['title'] ?? '');
    if ($title === '') {
      View::redirect('/');
    }
    $clientId = isset($_POST['client_id']) && $_POST['client_id'] !== '' ? (int) $_POST['client_id'] : null;
    $dueDate = $_POST['due_date'] ?? null;
    $priority = in_array($_POST['priority'] ?? '', ['low', 'high'], true) ? $_POST['priority'] : 'normal';

    TaskRepository::create(Auth::cabinetId(), Auth::id(), $title, $clientId, $dueDate, $priority);
    View::flash('success', 'Tâche ajoutée.');
    View::redirect($_POST['redirect'] ?? '/');
  }

  public static function complete(int $id): void
  {
    Auth::requireAuth();
    TaskRepository::markDone($id, Auth::cabinetId());
    View::redirect($_POST['redirect'] ?? '/');
  }
}
