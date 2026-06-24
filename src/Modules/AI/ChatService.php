<?php

declare(strict_types=1);

namespace App\Modules\AI;

use App\Core\Auth;
use App\Core\Database;

final class ChatService
{
    public static function getOrCreateSession(?int $clientId = null): int
    {
        $sessionId = $_SESSION['chat_session_id'] ?? null;
        if ($sessionId) {
            $exists = Database::fetchOne(
                'SELECT id FROM chat_sessions WHERE id = ? AND user_id = ? AND cabinet_id = ?',
                [$sessionId, Auth::id(), Auth::cabinetId()]
            );
            if ($exists) {
                if ($clientId) {
                    Database::query('UPDATE chat_sessions SET client_id = ? WHERE id = ?', [$clientId, $sessionId]);
                }
                return (int) $sessionId;
            }
        }
        $id = Database::insert(
            'INSERT INTO chat_sessions (cabinet_id, user_id, client_id, title) VALUES (?, ?, ?, ?)',
            [Auth::cabinetId(), Auth::id(), $clientId, 'Conversation ' . date('d/m/Y H:i')]
        );
        $_SESSION['chat_session_id'] = $id;
        return $id;
    }

    /** @return array<int, array{role: string, content: string}> */
    public static function history(int $sessionId, int $limit = 20): array
    {
        $rows = Database::fetchAll(
            'SELECT role, content FROM chat_messages WHERE session_id = ? AND role != ? ORDER BY id DESC LIMIT ' . (int) $limit,
            [$sessionId, 'system']
        );
        return array_reverse($rows);
    }

    public static function ask(string $message, ?int $clientId = null): string
    {
        $sessionId = self::getOrCreateSession($clientId);
        Database::insert(
            'INSERT INTO chat_messages (session_id, role, content) VALUES (?, ?, ?)',
            [$sessionId, 'user', $message]
        );

        $context = ChatContextBuilder::build($clientId);
        $history = self::history($sessionId, 10);

        $messages = [
            ['role' => 'system', 'content' => $context],
        ];
        foreach ($history as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }

        $reply = OpenRouterClient::chat($messages) ?? "Désolé, je n'ai pas pu obtenir une réponse. Vérifiez la clé OpenRouter.";

        Database::insert(
            'INSERT INTO chat_messages (session_id, role, content) VALUES (?, ?, ?)',
            [$sessionId, 'assistant', $reply]
        );
        Database::query('UPDATE chat_sessions SET updated_at = NOW() WHERE id = ?', [$sessionId]);

        return $reply;
    }

    public static function newSession(): void
    {
        unset($_SESSION['chat_session_id']);
    }
}
