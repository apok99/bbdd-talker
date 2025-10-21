<?php

return [
    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3'),
        'database_schema' => env('OLLAMA_DATABASE_SCHEMA', 'public'),
    ],
];
