<?php

declare(strict_types=1);

namespace App\Modules\AI;

final class OpenRouterClient
{
    public static function extractFields(string $rawText, string $docType, array $partial = []): ?array
    {
        $config = require ROOT_PATH . '/config/app.php';
        $apiKey = $config['openrouter_api_key'];
        if ($apiKey === '') {
            return null;
        }

        $schema = json_encode([
            'entry_type' => 'payroll|sales',
            'period_year' => 'int',
            'period_month' => 'int',
            'masse_salariale' => 'float',
            'salaire_base' => 'float',
            'employee_name' => 'string',
            'effectif' => 'int',
            'ca_biens' => 'float',
            'ca_services' => 'float',
            'irg_acompte_base' => 'float',
            'numero_cotisant' => 'string',
        ]);

        $prompt = "Extract structured accounting data from this Algerian document ($docType).\n"
            . "Return ONLY valid JSON matching fields: $schema\n"
            . "Partial template extraction: " . json_encode($partial) . "\n\n"
            . "Document text:\n" . mb_substr($rawText, 0, 6000);

        $payload = [
            'model' => $config['openrouter_model'],
            'messages' => [
                ['role' => 'system', 'content' => 'You extract structured JSON from French/Arabic accounting documents. JSON only, no markdown.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.1,
        ];

        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'HTTP-Referer: ' . ($config['url'] ?? 'http://localhost'),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code >= 400) {
            return null;
        }

        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? '';
        $content = trim($content);
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $content) ?? $content;
        }
        $parsed = json_decode($content, true);
        return is_array($parsed) ? $parsed : null;
    }

    /** @param array<int, array{role: string, content: string}> $messages */
    public static function chat(array $messages): ?string
    {
        $config = require ROOT_PATH . '/config/app.php';
        $apiKey = $config['openrouter_api_key'];
        if ($apiKey === '') {
            return null;
        }

        $payload = [
            'model' => $config['openrouter_model'],
            'messages' => $messages,
            'temperature' => 0.3,
        ];

        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'HTTP-Referer: ' . ($config['url'] ?? 'http://localhost'),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 90,
        ]);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code >= 400) {
            return null;
        }

        $data = json_decode($response, true);
        return trim($data['choices'][0]['message']['content'] ?? '') ?: null;
    }

    public static function jsonPrompt(string $userPrompt, string $systemPrompt = 'Return valid JSON only.'): ?array
    {
        $content = self::chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);
        if ($content === null) {
            return null;
        }
        $content = trim($content);
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $content) ?? $content;
        }
        $parsed = json_decode($content, true);
        return is_array($parsed) ? $parsed : null;
    }
}
