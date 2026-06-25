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
    View::flashT('success', 'flash.task_added');
    View::redirect($_POST['redirect'] ?? '/');
  }

  public static function complete(int $id): void
  {
    Auth::requireAuth();
    TaskRepository::markDone($id, Auth::cabinetId());
    View::redirect($_POST['redirect'] ?? '/');
  }

  public static function update(int $id): void
  {
    Auth::requireAuth();
    $title = trim($_POST['title'] ?? '');
    if ($title === '') {
      View::flashT('error', 'flash.task_title_required');
      View::redirect($_POST['redirect'] ?? '/tasks');
    }
    $clientId = isset($_POST['client_id']) && $_POST['client_id'] !== '' ? (int) $_POST['client_id'] : null;
    $priority = in_array($_POST['priority'] ?? '', ['low', 'high', 'normal'], true) ? $_POST['priority'] : 'normal';
    TaskRepository::update($id, Auth::cabinetId(), [
      'title' => $title,
      'client_id' => $clientId,
      'due_date' => $_POST['due_date'] ?? null,
      'priority' => $priority,
    ]);
    View::flashT('success', 'flash.task_updated');
    View::redirect($_POST['redirect'] ?? '/tasks');
  }

  public static function destroy(int $id): void
  {
    Auth::requireAuth();
    TaskRepository::delete($id, Auth::cabinetId());
    View::flashT('success', 'flash.task_deleted');
    View::redirect($_POST['redirect'] ?? '/tasks');
  }

  public static function reopen(int $id): void
  {
    Auth::requireAuth();
    TaskRepository::reopen($id, Auth::cabinetId());
    View::flashT('success', 'flash.task_reopened');
    View::redirect($_POST['redirect'] ?? '/tasks?filter=open');
  }
}
