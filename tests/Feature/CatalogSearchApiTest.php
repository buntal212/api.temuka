<?php

namespace Tests\Feature;

use App\Services\UIH\UihStreamClient;
use Mockery;
use Tests\TestCase;

class CatalogSearchApiTest extends TestCase
{
    public function test_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/catalog/search', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['search_id', 'query']);
    }

    public function test_rejects_unknown_fields(): void
    {
        $response = $this->postJson('/api/v1/catalog/search', [
            'search_id' => 's1',
            'query' => 'nasi',
            'unknown_field' => 'bad',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['unknown_field']);
    }

    public function test_proxies_uih_stream_to_client(): void
    {
        $mockUihClient = Mockery::mock(UihStreamClient::class);
        $mockUihClient->shouldReceive('streamSearch')
            ->once()
            ->with('s1', 'nasi', 20, Mockery::type('callable'))
            ->andReturnUsing(function ($sId, $q, $l, $callback) {
                $callback("event: search_init\ndata: {\"search_id\":\"s1\"}\n\n");
                $callback("event: search_done\ndata: {\"search_id\":\"s1\"}\n\n");
            });

        $this->app->instance(UihStreamClient::class, $mockUihClient);

        $response = $this->postJson('/api/v1/catalog/search', [
            'search_id' => 's1',
            'query' => 'nasi',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertSame('no-cache, no-transform', $response->headers->get('Cache-Control'));
        $this->assertSame('no', $response->headers->get('X-Accel-Buffering'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('event: search_init', $content);
        $this->assertStringContainsString('event: search_done', $content);
    }
}
