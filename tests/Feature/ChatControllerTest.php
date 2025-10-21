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
        $service->shouldReceive('availableDatabases')
            ->once()
            ->andReturn([
                'pgsql' => ['connection' => 'pgsql', 'database' => 'postgres', 'label' => 'postgres (pgsql)'],
            ]);
        $service->shouldReceive('defaultDatabase')
            ->once()
            ->andReturn('pgsql');
        $service->shouldReceive('availableSchemas')
            ->once()
            ->with('pgsql')
            ->andReturn(['public', 'ventas']);
        $service->shouldReceive('defaultSchema')
            ->once()
            ->andReturn('public');
        $service->shouldReceive('availableTables')
            ->once()
            ->with('pgsql', 'public')
            ->andReturn(['clientes']);
        $service->shouldReceive('columnsForTable')
            ->once()
            ->with('pgsql', 'public', 'clientes')
            ->andReturn([
                ['name' => 'id', 'type' => 'integer', 'nullable' => false, 'default' => null],
            ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Describe la información que necesitas');
        $response->assertSee('Base de datos');
        $response->assertSee('Esquema');
        $response->assertSee('Tabla');
        $response->assertSee('ventas');
        $response->assertSee('Columnas de clientes');
        $response->assertSee('id');
    }

    public function test_send_uses_selected_schema_and_persists_conversation(): void
    {
        $service = $this->mock(DatabaseAwareChatService::class);
        $service->shouldReceive('availableDatabases')
            ->once()
            ->andReturn([
                'pgsql' => ['connection' => 'pgsql', 'database' => 'postgres', 'label' => 'postgres (pgsql)'],
            ]);
        $service->shouldReceive('defaultDatabase')
            ->once()
            ->andReturn('pgsql');
        $service->shouldReceive('availableSchemas')
            ->once()
            ->with('pgsql')
            ->andReturn(['public', 'ventas']);
        $service->shouldReceive('defaultSchema')
            ->twice()
            ->andReturn('public');
        $service->shouldReceive('reply')
            ->once()
            ->with('Hola', Mockery::on(function ($conversation) {
                return collect($conversation)->contains(function ($message) {
                    return $message['role'] === 'user' && $message['content'] === 'Hola';
                });
            }), 'pgsql', 'ventas')
            ->andReturn('Respuesta generada');

        $response = $this->post('/enviar', [
            'message' => 'Hola',
            'database' => 'pgsql',
            'schema' => 'ventas',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('chat.conversation');
        $response->assertSessionHas('chat.database', 'pgsql');
        $response->assertSessionHas('chat.schema', 'ventas');

        $conversation = session('chat.conversation');
        $this->assertSame('Respuesta generada', $conversation[1]['content']);
    }

    public function test_index_allows_selecting_schema_and_table_via_query(): void
    {
        $service = $this->mock(DatabaseAwareChatService::class);
        $service->shouldReceive('availableDatabases')
            ->once()
            ->andReturn([
                'pgsql' => ['connection' => 'pgsql', 'database' => 'postgres', 'label' => 'postgres (pgsql)'],
            ]);
        $service->shouldReceive('defaultDatabase')
            ->once()
            ->andReturn('pgsql');
        $service->shouldReceive('availableSchemas')
            ->once()
            ->with('pgsql')
            ->andReturn(['public', 'ventas']);
        $service->shouldReceive('defaultSchema')
            ->once()
            ->andReturn('public');
        $service->shouldReceive('availableTables')
            ->once()
            ->with('pgsql', 'ventas')
            ->andReturn(['facturas', 'clientes']);
        $service->shouldReceive('columnsForTable')
            ->once()
            ->with('pgsql', 'ventas', 'facturas')
            ->andReturn([
                ['name' => 'numero', 'type' => 'text', 'nullable' => false, 'default' => null],
            ]);

        $response = $this->get('/?database=pgsql&schema=ventas&table=facturas');

        $response->assertStatus(200);
        $response->assertSee('Columnas de facturas');
        $response->assertSee('numero');
        $response->assertSessionHas('chat.database', 'pgsql');
        $response->assertSessionHas('chat.schema', 'ventas');
        $response->assertSessionHas('chat.table', 'facturas');
    }

    public function test_reset_clears_conversation_and_schema(): void
    {
        $this->mock(DatabaseAwareChatService::class);

        $response = $this->withSession([
            'chat.conversation' => [['role' => 'user', 'content' => 'Hola']],
            'chat.schema' => 'ventas',
            'chat.database' => 'pgsql',
            'chat.table' => 'clientes',
        ])->post('/reiniciar');

        $response->assertRedirect('/');
        $response->assertSessionMissing('chat.conversation');
        $response->assertSessionMissing('chat.schema');
        $response->assertSessionMissing('chat.database');
        $response->assertSessionMissing('chat.table');
    }
}
