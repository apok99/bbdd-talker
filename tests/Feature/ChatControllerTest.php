<?php

namespace Tests\Feature;

use App\Services\Chat\DatabaseAwareChatService;
use Mockery;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    public function test_index_loads_empty_conversation(): void
    {
        $this->mock(DatabaseAwareChatService::class)
            ->shouldReceive('availableSchemas')
            ->once()
            ->andReturn(['public', 'ventas']);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Describe la información que necesitas');
        $response->assertSee('Esquema');
        $response->assertSee('ventas');
    }

    public function test_send_uses_selected_schema_and_persists_conversation(): void
    {
        $service = $this->mock(DatabaseAwareChatService::class);
        $service->shouldReceive('availableSchemas')
            ->once()
            ->andReturn(['public', 'ventas']);
        $service->shouldReceive('reply')
            ->once()
            ->with('Hola', Mockery::on(function ($conversation) {
                return collect($conversation)->contains(function ($message) {
                    return $message['role'] === 'user' && $message['content'] === 'Hola';
                });
            }), 'ventas')
            ->andReturn('Respuesta generada');

        $response = $this->post('/enviar', [
            'message' => 'Hola',
            'schema' => 'ventas',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('chat.conversation');
        $response->assertSessionHas('chat.schema', 'ventas');

        $conversation = session('chat.conversation');
        $this->assertSame('Respuesta generada', $conversation[1]['content']);
    }

    public function test_reset_clears_conversation_and_schema(): void
    {
        $this->mock(DatabaseAwareChatService::class);

        $response = $this->withSession([
            'chat.conversation' => [['role' => 'user', 'content' => 'Hola']],
            'chat.schema' => 'ventas',
        ])->post('/reiniciar');

        $response->assertRedirect('/');
        $response->assertSessionMissing('chat.conversation');
        $response->assertSessionMissing('chat.schema');
    }
}
