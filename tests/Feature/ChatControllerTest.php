<?php

namespace Tests\Feature;

use App\Services\Chat\DatabaseAwareChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    public function test_index_loads_empty_conversation(): void
    {
        $this->mock(DatabaseAwareChatService::class);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Describe la información que necesitas');
    }
}
