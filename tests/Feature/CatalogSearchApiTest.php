<?php

namespace Tests\Feature;

use App\Services\UIH\UihStreamClient;
use Illuminate\Support\Str;
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
            ->with('s1', 'nasi', 20, Mockery::on(fn ($cid) => is_string($cid) && Str::isUuid($cid)), Mockery::type('callable'))
            ->andReturnUsing(function ($sId, $q, $l, $cid, $callback) use (&$content) {
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
        $this->assertTrue(Str::isUuid((string) $response->headers->get('X-Correlation-ID')));

        $initialBufferLevel = ob_get_level();
        $response->sendContent();
        while (ob_get_level() < $initialBufferLevel) {
            ob_start();
        }

        $this->assertStringContainsString('event: search_init', $content);
        $this->assertStringContainsString('event: search_done', $content);
    }

    public function test_validates_limit_boundaries(): void
    {
        $mockUihClient = Mockery::mock(UihStreamClient::class);
        $mockUihClient->shouldReceive('streamSearch')->zeroOrMoreTimes();
        $this->app->instance(UihStreamClient::class, $mockUihClient);

        // Limit 50 accepted
        $responseAccepted = $this->postJson('/api/v1/catalog/search', [
            'search_id' => 's_limit_50',
            'query' => 'nasi',
            'limit' => 50,
        ]);
        $responseAccepted->assertStatus(200);

        // Limit 51 rejected
        $responseOver = $this->postJson('/api/v1/catalog/search', [
            'search_id' => 's_limit_51',
            'query' => 'nasi',
            'limit' => 51,
        ]);
        $responseOver->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);

        // Limit 0 rejected
        $responseUnder = $this->postJson('/api/v1/catalog/search', [
            'search_id' => 's_limit_0',
            'query' => 'nasi',
            'limit' => 0,
        ]);
        $responseUnder->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    public function test_preserves_valid_incoming_correlation_id(): void
    {
        $expectedCid = 'a1111111-2222-3333-4444-555555555555';
        $mockUihClient = Mockery::mock(UihStreamClient::class);
        $mockUihClient->shouldReceive('streamSearch')
            ->once()
            ->with('s_cid_valid', 'nasi', 20, $expectedCid, Mockery::type('callable'));
        $this->app->instance(UihStreamClient::class, $mockUihClient);

        $response = $this->withHeader('X-Correlation-ID', $expectedCid)
            ->postJson('/api/v1/catalog/search', [
                'search_id' => 's_cid_valid',
                'query' => 'nasi',
            ]);

        $response->assertStatus(200);
        $this->assertSame($expectedCid, $response->headers->get('X-Correlation-ID'));

        $initialBufferLevel = ob_get_level();
        $response->sendContent();
        while (ob_get_level() < $initialBufferLevel) {
            ob_start();
        }
    }

    public function test_replaces_invalid_correlation_id_with_new_uuid(): void
    {
        $capturedCid = null;
        $mockUihClient = Mockery::mock(UihStreamClient::class);
        $mockUihClient->shouldReceive('streamSearch')
            ->once()
            ->with('s_cid_invalid', 'nasi', 20, Mockery::on(function ($cid) use (&$capturedCid) {
                $capturedCid = $cid;

                return is_string($cid) && Str::isUuid($cid);
            }), Mockery::type('callable'));
        $this->app->instance(UihStreamClient::class, $mockUihClient);

        $response = $this->withHeader('X-Correlation-ID', 'not-a-valid-uuid')
            ->postJson('/api/v1/catalog/search', [
                'search_id' => 's_cid_invalid',
                'query' => 'nasi',
            ]);

        $response->assertStatus(200);
        $this->assertNotSame('not-a-valid-uuid', $response->headers->get('X-Correlation-ID'));
        $this->assertTrue(Str::isUuid((string) $response->headers->get('X-Correlation-ID')));

        $initialBufferLevel = ob_get_level();
        $response->sendContent();
        while (ob_get_level() < $initialBufferLevel) {
            ob_start();
        }

        $this->assertSame($capturedCid, $response->headers->get('X-Correlation-ID'));
    }

    public function test_generates_and_forwards_new_uuid_when_correlation_id_header_absent(): void
    {
        $capturedCid = null;
        $mockUihClient = Mockery::mock(UihStreamClient::class);
        $mockUihClient->shouldReceive('streamSearch')
            ->once()
            ->with('s_cid_absent', 'nasi', 20, Mockery::on(function ($cid) use (&$capturedCid) {
                $capturedCid = $cid;

                return is_string($cid) && Str::isUuid($cid);
            }), Mockery::type('callable'));
        $this->app->instance(UihStreamClient::class, $mockUihClient);

        // Header X-Correlation-ID is deliberately NOT provided
        $response = $this->postJson('/api/v1/catalog/search', [
            'search_id' => 's_cid_absent',
            'query' => 'nasi',
        ]);

        $response->assertStatus(200);
        $responseCid = $response->headers->get('X-Correlation-ID');
        $this->assertNotEmpty($responseCid);
        $this->assertTrue(Str::isUuid((string) $responseCid));

        $initialBufferLevel = ob_get_level();
        $response->sendContent();
        while (ob_get_level() < $initialBufferLevel) {
            ob_start();
        }

        $this->assertSame($capturedCid, $responseCid);
    }
}
