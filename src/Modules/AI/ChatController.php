<?php

declare(strict_types=1);

namespace App\Modules\AI;

use App\Core\Auth;
use App\Core\View;
use App\Modules\Clients\ClientRepository;

final class ChatController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $clientId = isset($_GET['client']) ? (int) $_GET['client'] : null;
        $sessionId = ChatService::getOrCreateSession($clientId);
        View::render('assistant/index', [
            'title' => 'Assistant IA',
            'clients' => ClientRepository::allForCabinet(),
            'clientId' => $clientId,
            'history' => ChatService::history($sessionId, 30),
        ]);
    }

    public static function chat(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST;
        $message = trim($input['message'] ?? '');
        $clientId = !empty($input['client_id']) ? (int) $input['client_id'] : null;

        if ($message === '') {
            echo json_encode(['error' => 'Message vide']);
            return;
        }

        $reply = ChatService::ask($message, $clientId);
        echo json_encode(['reply' => $reply]);
    }

    public static function newSession(): void
    {
        Auth::requireAuth();
        ChatService::newSession();
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            return;
        }
        View::redirect('/assistant');
    }
}
