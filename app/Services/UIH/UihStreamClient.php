<?php

namespace App\Services\UIH;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Throwable;

class UihStreamClient
{
    protected ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $baseUrl = rtrim((string) config('services.uih.base_url', 'http://127.0.0.1:8011'), '/');

        $this->client = $client ?: new Client([
            'base_uri' => $baseUrl,
            'timeout' => (float) config('services.uih.timeout', 10.0),
            'http_errors' => false,
        ]);
    }

    /**
     * Stream catalog search results from UIH incrementally.
     *
     * @param callable(string $chunk): void $onChunk
     */
    public function streamSearch(string $searchId, string $query, int $limit, callable $onChunk): void
    {
        $token = (string) config('services.uih.server_token', 'uih-dev-internal-token');

        try {
            $response = $this->client->request('POST', '/api/v1/catalog/search', [
                'stream' => true,
                'headers' => [
                    'Accept' => 'text/event-stream',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => [
                    'search_id' => $searchId,
                    'query' => $query,
                    'limit' => $limit,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                // Emit synthetic failure event if UIH returned an HTTP error
                $errorPayload = json_encode([
                    'search_id' => $searchId,
                    'source' => 'uih_gateway',
                    'status' => 'failed',
                    'error_code' => 'GATEWAY_HTTP_' . $statusCode,
                    'message' => 'UIH Gateway mengembalikan respon kesalahan.',
                ], JSON_UNESCAPED_SLASHES);

                $donePayload = json_encode([
                    'search_id' => $searchId,
                    'total_items' => 0,
                    'summary' => ['uih_gateway' => 'failed'],
                ], JSON_UNESCAPED_SLASHES);

                $onChunk("event: source_failed\ndata: {$errorPayload}\n\n");
                $onChunk("event: search_done\ndata: {$donePayload}\n\n");

                return;
            }

            $body = $response->getBody();
            while (! $body->eof()) {
                $chunk = $body->read(1024);
                if ($chunk !== '') {
                    $onChunk($chunk);
                }
            }
        } catch (Throwable $e) {
            $errorPayload = json_encode([
                'search_id' => $searchId,
                'source' => 'uih_gateway',
                'status' => 'failed',
                'error_code' => 'GATEWAY_CONNECTION_ERROR',
                'message' => 'Tidak dapat terhubung ke UIH Gateway: ' . $e->getMessage(),
            ], JSON_UNESCAPED_SLASHES);

            $donePayload = json_encode([
                'search_id' => $searchId,
                'total_items' => 0,
                'summary' => ['uih_gateway' => 'failed'],
            ], JSON_UNESCAPED_SLASHES);

            $onChunk("event: source_failed\ndata: {$errorPayload}\n\n");
            $onChunk("event: search_done\ndata: {$donePayload}\n\n");
        }
    }
}
