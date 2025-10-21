<?php

namespace App\Http\Controllers;

use App\Services\Chat\DatabaseAwareChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
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

        $availableSchemas = $this->chatService->availableSchemas();
        $defaultSchema = Config::get('services.ollama.database_schema', 'public');

        $selectedSchema = $request->query('schema', $request->session()->get('chat.schema', $defaultSchema));

        if (! in_array($selectedSchema, $availableSchemas, true)) {
            $selectedSchema = $defaultSchema;
        }

        $request->session()->put('chat.schema', $selectedSchema);

        $tables = $this->chatService->availableTables($selectedSchema);

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
            ? $this->chatService->columnsForTable($selectedSchema, $selectedTable)
            : [];

        return view('chat', [
            'conversation' => $conversation,
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
            'schema' => ['nullable', 'string'],
        ]);

        $conversation = $request->session()->get('chat.conversation', []);
        $conversation[] = [
            'role' => 'user',
            'content' => $validated['message'],
        ];

        $availableSchemas = $this->chatService->availableSchemas();

        $schema = $validated['schema']
            ?: Config::get('services.ollama.database_schema', 'public');

        if (! in_array($schema, $availableSchemas, true)) {
            $schema = Config::get('services.ollama.database_schema', 'public');
        }

        $request->session()->put('chat.schema', $schema);

        try {
            $reply = $this->chatService->reply($validated['message'], $conversation, $schema);

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
        $request->session()->forget(['chat.conversation', 'chat.schema']);

        return back();
    }
}
