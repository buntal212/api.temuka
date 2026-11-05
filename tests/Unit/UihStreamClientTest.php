<?php

namespace Tests\Unit;

use App\Services\UIH\UihStreamClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class UihStreamClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        Container::setInstance($container);
        $container->instance('config', new Repository([
            'services' => [
                'uih' => [
                    'base_url' => 'http://uih-dev-app:8000',
                    'server_token' => 'test-s2s-token',
                    'timeout' => 10.0,
                ],
            ],
        ]));
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_streams_chunks_incrementally(): void
    {
        $mockStreamData = "event: search_init\ndata: {\"search_id\":\"s1\"}\n\nevent: search_done\ndata: {\"search_id\":\"s1\"}\n\n";

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/event-stream'], $mockStreamData),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'http_errors' => false]);

        $uihClient = new UihStreamClient($client);

        $receivedChunks = [];
        $uihClient->streamSearch('s1', 'nasi', 10, 'correlation-123', function (string $chunk) use (&$receivedChunks): void {
            $receivedChunks[] = $chunk;
        });

        $this->assertNotEmpty($receivedChunks);
        $fullOutput = implode('', $receivedChunks);
        $this->assertStringContainsString('event: search_init', $fullOutput);
        $this->assertStringContainsString('event: search_done', $fullOutput);
    }

    public function test_passes_correlation_id_header_to_uih_gateway(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/event-stream'], "event: search_done\ndata: {}\n\n"),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'http_errors' => false]);

        $uihClient = new UihStreamClient($client);

        $expectedCorrelationId = 'c74f5cb1-7f9a-4cbe-b4db-99eebda3ef91';
        $uihClient->streamSearch('s1', 'nasi', 10, $expectedCorrelationId, function (): void {});

        $lastRequest = $mock->getLastRequest();
        $this->assertNotNull($lastRequest);
        $this->assertSame([$expectedCorrelationId], $lastRequest->getHeader('X-Correlation-ID'));
    }

    public function test_emits_synthetic_failure_events_on_gateway_error(): void
    {
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'http_errors' => false]);

        $uihClient = new UihStreamClient($client);

        $receivedChunks = [];
        $uihClient->streamSearch('s2', 'nasi', 10, 'correlation-456', function (string $chunk) use (&$receivedChunks): void {
            $receivedChunks[] = $chunk;
        });

        $fullOutput = implode('', $receivedChunks);
        $this->assertStringContainsString('event: source_failed', $fullOutput);
        $this->assertStringContainsString('GATEWAY_HTTP_500', $fullOutput);
        $this->assertStringContainsString('event: search_done', $fullOutput);
    }

    public function test_emits_safe_error_event_without_leaking_exception_details_on_connection_failure(): void
    {
        $sensitiveHost = 'uih-internal-hidden.corp:8011';
        $sensitivePath = '/secret/internal/path';
        $mock = new MockHandler([
            new ConnectException(
                "cURL error 7: Failed to connect to {$sensitiveHost}{$sensitivePath}",
                new Request('POST', '/api/v1/catalog/search')
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack, 'http_errors' => false]);

        $uihClient = new UihStreamClient($client);

        $receivedChunks = [];
        $uihClient->streamSearch('s3', 'nasi', 10, 'cid-connect-fail', function (string $chunk) use (&$receivedChunks): void {
            $receivedChunks[] = $chunk;
        });

        $fullOutput = implode('', $receivedChunks);
        $this->assertStringContainsString('event: source_failed', $fullOutput);
        $this->assertStringContainsString('GATEWAY_CONNECTION_ERROR', $fullOutput);
        $this->assertStringContainsString('Tidak dapat terhubung ke UIH Gateway.', $fullOutput);
        $this->assertStringNotContainsString($sensitiveHost, $fullOutput);
        $this->assertStringNotContainsString($sensitivePath, $fullOutput);
        $this->assertStringContainsString('event: search_done', $fullOutput);
    }
}
