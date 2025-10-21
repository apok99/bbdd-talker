<?php

namespace App\Services\Chat;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DatabaseAwareChatService
{
    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    public function reply(string $prompt, array $history, string $schema): string
    {
        $systemPrompt = $this->buildSystemPrompt($schema);

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

    public function availableSchemas(): array
    {
        $defaultSchema = Config::get('services.ollama.database_schema', 'public');

        try {
            $schemas = $this->connection->select(
                "SELECT schema_name FROM information_schema.schemata WHERE schema_name NOT LIKE 'pg_%' AND schema_name <> 'information_schema' ORDER BY schema_name"
            );

            return collect($schemas)
                ->pluck('schema_name')
                ->push($defaultSchema)
                ->unique()
                ->values()
                ->all();
        } catch (Throwable $exception) {
            Log::warning('Failed to fetch database schemas', [
                'exception' => $exception,
            ]);

            return [$defaultSchema];
        }
    }

    public function availableTables(string $schema): array
    {
        return $this->fetchTablesForSchema($schema);
    }

    public function columnsForTable(string $schema, string $table): array
    {
        return $this->fetchColumnsForTable($schema, $table);
    }

    private function buildSystemPrompt(string $schema): string
    {
        $tables = collect($this->fetchTablesForSchema($schema));

        $tablesList = $tables->map(function (string $table) use ($schema) {
            $columns = collect($this->fetchColumnsForTable($schema, $table));

            if ($columns->isEmpty()) {
                return "- {$table} (sin columnas detectadas)";
            }

            $columnList = $columns->map(function (array $column) {
                $nullability = $column['nullable'] ? 'NULL' : 'NOT NULL';
                $default = $column['default'] !== null
                    ? " (por defecto: {$column['default']})"
                    : '';

                return "    - {$column['name']} ({$column['type']}, {$nullability}{$default})";
            })->implode("\n");

            return "- {$table}\n{$columnList}";
        })->implode("\n");

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

    private function fetchTablesForSchema(string $schema): array
    {
        try {
            $tables = $this->connection->select(
                'SELECT table_name FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name',
                [$schema]
            );

            return collect($tables)
                ->pluck('table_name')
                ->values()
                ->all();
        } catch (Throwable $exception) {
            Log::warning('Failed to fetch tables for schema', [
                'schema' => $schema,
                'exception' => $exception,
            ]);

            return [];
        }
    }

    private function fetchColumnsForTable(string $schema, string $table): array
    {
        try {
            $columns = $this->connection->select(
                'SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = ? AND table_name = ? ORDER BY ordinal_position',
                [$schema, $table]
            );

            return collect($columns)->map(function ($column) {
                $columnArray = (array) $column;

                return [
                    'name' => $columnArray['column_name'] ?? '',
                    'type' => $columnArray['data_type'] ?? '',
                    'nullable' => ($columnArray['is_nullable'] ?? 'NO') === 'YES',
                    'default' => $columnArray['column_default'] ?? null,
                ];
            })->all();
        } catch (Throwable $exception) {
            Log::warning('Failed to fetch columns for table', [
                'schema' => $schema,
                'table' => $table,
                'exception' => $exception,
            ]);

            return [];
        }
    }
}
