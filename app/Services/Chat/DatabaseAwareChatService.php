<?php

namespace App\Services\Chat;

use Illuminate\Contracts\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DatabaseAwareChatService
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function reply(string $prompt, array $history): string
    {
        $systemPrompt = $this->buildSystemPrompt();

        $messages = collect($history)
            ->map(fn (array $message) => [
                'role' => Arr::get($message, 'role', 'user'),
                'content' => Arr::get($message, 'content', ''),
            ])->values()->all();

        array_unshift($messages, [
            'role' => 'system',
            'content' => $systemPrompt,
        ]);

        $response = Http::baseUrl(Config::get('services.ollama.base_url'))
            ->timeout(120)
            ->post('/api/chat', [
                'model' => Config::get('services.ollama.model'),
                'messages' => array_merge($messages, [[
                    'role' => 'user',
                    'content' => $prompt,
                ]]),
                'stream' => false,
            ])->throw()->json();

        $answer = data_get($response, 'message.content', '');

        if (Str::contains(Str::lower($answer), 'sql')) {
            $execution = $this->executeSqlFromAnswer($answer);
            if ($execution !== '') {
                $answer .= "\n\n".$execution;
            }
        }

        return $answer;
    }

    private function buildSystemPrompt(): string
    {
        $schema = Config::get('services.ollama.database_schema', 'public');
        $tables = $this->connection->select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$schema]);

        $tablesList = collect($tables)
            ->pluck('table_name')
            ->map(fn ($table) => "- {$table}")
            ->implode("\n");

        if ($tablesList === '') {
            $tablesList = '- (sin tablas detectadas)';
        }

        return <<<PROMPT
Eres un asistente experto en PostgreSQL. El usuario te pedirá información sobre la base de datos. Sigue siempre estos pasos:
1. Analiza la petición y diseña la consulta SQL más adecuada usando el esquema {$schema}.
2. Devuelve la consulta dentro de un bloque ```sql```. Procura que sea segura y que limite resultados cuando tenga sentido.
3. Ejecuta la consulta en la base de datos y muestra una tabla con los resultados o un resumen claro.
4. Si la consulta puede ser destructiva, responde que no puedes ejecutar esa acción.

El esquema {$schema} contiene estas tablas:
{$tablesList}
PROMPT;
    }

    private function executeSqlFromAnswer(string $answer): string
    {
        if (! preg_match('/```sql\s*(.*?)```/is', $answer, $matches)) {
            return '';
        }

        $sql = trim($matches[1]);

        if (! Str::startsWith(Str::lower($sql), ['select', 'with'])) {
            return 'Por seguridad, solo se ejecutan consultas de lectura.';
        }

        $results = $this->connection->select($sql);

        if (empty($results)) {
            return 'La consulta no devolvió resultados.';
        }

        $headers = array_keys((array) $results[0]);
        $rows = collect($results)->map(function ($row) use ($headers) {
            $rowArray = (array) $row;
            return '| '.collect($headers)->map(fn ($column) => (string) ($rowArray[$column] ?? ''))->implode(' | ').' |';
        });

        $headerRow = '| '.implode(' | ', $headers).' |';
        $separator = '|'.collect($headers)->map(fn ($column) => str_repeat('-', strlen($column) + 2))->implode('|').'|';

        return implode("\n", [$headerRow, $separator, ...$rows]);
    }
}
