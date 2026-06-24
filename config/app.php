<?php

return [
    'name' => 'Cabinet Comptable',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8080',
    'timezone' => 'Africa/Algiers',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    'encryption_key' => $_ENV['APP_KEY'] ?? 'change-me-to-32-char-secret-key!',
    'openrouter_api_key' => $_ENV['OPENROUTER_API_KEY'] ?? $_ENV['apikeyopenrouter'] ?? '',
    'openrouter_model' => $_ENV['OPENROUTER_MODEL'] ?? 'openai/gpt-4o-mini',
    'tesseract_path' => $_ENV['TESSERACT_PATH'] ?? 'tesseract',
];
