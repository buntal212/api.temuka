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
        $content = '';
        $mockUihClient = Mockery::mock(UihStreamClient::class);
        $mockUihClient->shouldReceive('streamSearch')
            ->once()
            ->with('s1', 'nasi', 20, Mockery::type('callable'))
            ->andReturnUsing(function ($sId, $q, $l, $callback) use (&$content) {
                ob_start(function (string $chunk) use (&$content): string {
                    $content .= $chunk;

                    return '';
                });

                try {
                    $callback("event: search_init\ndata: {\"search_id\":\"s1\"}\n\n");
                    $callback("event: search_done\ndata: {\"search_id\":\"s1\"}\n\n");
                } finally {
                    if (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                }
            });

        $this->app->instance(UihStreamClient::class, $mockUihClient);

        $response = $this->postJson('/api/v1/catalog/search', [
            'search_id' => 's1',
            'query' => 'nasi',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-transform', $cacheControl);
        $this->assertSame('no', $response->headers->get('X-Accel-Buffering'));

        $initialBufferLevel = ob_get_level();
        $response->sendContent();
        while (ob_get_level() < $initialBufferLevel) {
            ob_start();
        }

        $this->assertStringContainsString('event: search_init', $content);
        $this->assertStringContainsString('event: search_done', $content);
    }
}
