<?php

namespace App\Http\Controllers;

use App\Services\Chat\DatabaseAwareChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(private readonly DatabaseAwareChatService $chatService)
    {
    }

    public function index(Request $request): View
    {
        $conversation = $request->session()->get('chat.conversation', []);

        $availableDatabases = $this->chatService->availableDatabases();
        $defaultDatabase = $this->chatService->defaultDatabase();

        if (! array_key_exists($defaultDatabase, $availableDatabases)) {
            $defaultDatabase = array_key_first($availableDatabases) ?? $defaultDatabase;
        }

        $selectedDatabase = $request->query('database', $request->session()->get('chat.database', $defaultDatabase));

        if (! array_key_exists($selectedDatabase, $availableDatabases)) {
            $selectedDatabase = $defaultDatabase;
        }

        $request->session()->put('chat.database', $selectedDatabase);

        $availableSchemas = $this->chatService->availableSchemas($selectedDatabase);
        $defaultSchema = $this->chatService->defaultSchema();

        $selectedSchema = $request->query('schema', $request->session()->get('chat.schema', $defaultSchema));

        if (! in_array($selectedSchema, $availableSchemas, true)) {
            $selectedSchema = $defaultSchema;
        }

        $request->session()->put('chat.schema', $selectedSchema);

        $tables = $this->chatService->availableTables($selectedDatabase, $selectedSchema);

        $selectedTable = $request->query('table', $request->session()->get('chat.table'));

        if (! in_array($selectedTable, $tables, true)) {
            $selectedTable = $tables[0] ?? null;
        }

        if ($selectedTable !== null) {
            $request->session()->put('chat.table', $selectedTable);
        } else {
            $request->session()->forget('chat.table');
        }

        $tableColumns = $selectedTable
            ? $this->chatService->columnsForTable($selectedDatabase, $selectedSchema, $selectedTable)
            : [];

        return view('chat', [
            'conversation' => $conversation,
            'databases' => $availableDatabases,
            'selectedDatabase' => $selectedDatabase,
            'schemas' => $availableSchemas,
            'selectedSchema' => $selectedSchema,
            'tables' => $tables,
            'selectedTable' => $selectedTable,
            'tableColumns' => $tableColumns,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'database' => ['nullable', 'string'],
            'schema' => ['nullable', 'string'],
        ]);

        $conversation = $request->session()->get('chat.conversation', []);
        $conversation[] = [
            'role' => 'user',
            'content' => $validated['message'],
        ];

        $availableDatabases = $this->chatService->availableDatabases();
        $defaultDatabase = $this->chatService->defaultDatabase();

        if (! array_key_exists($defaultDatabase, $availableDatabases)) {
            $defaultDatabase = array_key_first($availableDatabases) ?? $defaultDatabase;
        }

        $database = $validated['database'] ?: $defaultDatabase;

        if (! array_key_exists($database, $availableDatabases)) {
            $database = $defaultDatabase;
        }

        $request->session()->put('chat.database', $database);

        $availableSchemas = $this->chatService->availableSchemas($database);

        $schema = $validated['schema']
            ?: $this->chatService->defaultSchema();

        if (! in_array($schema, $availableSchemas, true)) {
            $schema = $this->chatService->defaultSchema();
        }

        $request->session()->put('chat.schema', $schema);

        try {
            $reply = $this->chatService->reply($validated['message'], $conversation, $database, $schema);

            $conversation[] = [
                'role' => 'assistant',
                'content' => $reply,
            ];

            $request->session()->put('chat.conversation', $conversation);
        } catch (\Throwable $exception) {
            Log::error('Failed to request Ollama response', [
                'exception' => $exception,
            ]);

            return back()->withErrors([
                'message' => 'No se pudo contactar con el modelo Ollama. Revisa que el servidor local esté encendido.',
            ])->withInput();
        }

        return back();
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget(['chat.conversation', 'chat.schema', 'chat.database', 'chat.table']);

        return back();
    }
}
