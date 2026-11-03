<?php

namespace Tests\Unit;

use App\Services\UIH\UihStreamClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class UihStreamClientTest extends TestCase
{
    public function test_streams_chunks_incrementally(): void
    {
        $mockStreamData = "event: search_init\ndata: {\"search_id\":\"s1\"}\n\nevent: search_done\ndata: {\"search_id\":\"s1\"}\n\n";

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/event-stream'], $mockStreamData),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $uihClient = new UihStreamClient($client);

        $receivedChunks = [];
        $uihClient->streamSearch('s1', 'nasi', 10, function (string $chunk) use (&$receivedChunks): void {
            $receivedChunks[] = $chunk;
        });

        $this->assertNotEmpty($receivedChunks);
        $fullOutput = implode('', $receivedChunks);
        $this->assertStringContainsString('event: search_init', $fullOutput);
        $this->assertStringContainsString('event: search_done', $fullOutput);
    }

    public function test_emits_synthetic_failure_events_on_gateway_error(): void
    {
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $uihClient = new UihStreamClient($client);

        $receivedChunks = [];
        $uihClient->streamSearch('s2', 'nasi', 10, function (string $chunk) use (&$receivedChunks): void {
            $receivedChunks[] = $chunk;
        });

        $fullOutput = implode('', $receivedChunks);
        $this->assertStringContainsString('event: source_failed', $fullOutput);
        $this->assertStringContainsString('GATEWAY_HTTP_500', $fullOutput);
        $this->assertStringContainsString('event: search_done', $fullOutput);
    }
}
