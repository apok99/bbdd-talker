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

        return view('chat', [
            'conversation' => $conversation,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $conversation = $request->session()->get('chat.conversation', []);
        $conversation[] = [
            'role' => 'user',
            'content' => $validated['message'],
        ];

        try {
            $reply = $this->chatService->reply($validated['message'], $conversation);

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
        $request->session()->forget('chat.conversation');

        return back();
    }
}
