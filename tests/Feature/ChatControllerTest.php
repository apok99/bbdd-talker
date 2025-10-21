<?php

namespace Tests\Feature;

use App\Services\Chat\DatabaseAwareChatService;
use Mockery;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    public function test_index_loads_empty_conversation(): void
    {
        $service = $this->mock(DatabaseAwareChatService::class);
        $service->shouldReceive('availableSchemas')
            ->once()
            ->andReturn(['public', 'ventas']);
        $service->shouldReceive('availableTables')
            ->once()
            ->with('public')
            ->andReturn(['clientes']);
        $service->shouldReceive('columnsForTable')
            ->once()
            ->with('public', 'clientes')
            ->andReturn([
                ['name' => 'id', 'type' => 'integer', 'nullable' => false, 'default' => null],
            ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Describe la información que necesitas');
        $response->assertSee('Esquema');
        $response->assertSee('Tabla');
        $response->assertSee('ventas');
        $response->assertSee('Columnas de clientes');
        $response->assertSee('id');
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

    public function test_index_allows_selecting_schema_and_table_via_query(): void
    {
        $service = $this->mock(DatabaseAwareChatService::class);
        $service->shouldReceive('availableSchemas')
            ->once()
            ->andReturn(['public', 'ventas']);
        $service->shouldReceive('availableTables')
            ->once()
            ->with('ventas')
            ->andReturn(['facturas', 'clientes']);
        $service->shouldReceive('columnsForTable')
            ->once()
            ->with('ventas', 'facturas')
            ->andReturn([
                ['name' => 'numero', 'type' => 'text', 'nullable' => false, 'default' => null],
            ]);

        $response = $this->get('/?schema=ventas&table=facturas');

        $response->assertStatus(200);
        $response->assertSee('Columnas de facturas');
        $response->assertSee('numero');
        $response->assertSessionHas('chat.schema', 'ventas');
        $response->assertSessionHas('chat.table', 'facturas');
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
