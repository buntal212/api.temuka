<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CatalogSearchRequest;
use App\Services\UIH\UihStreamClient;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogSearchController extends Controller
{
    public function __construct(
        protected UihStreamClient $uihClient
    ) {}

    public function __invoke(CatalogSearchRequest $request): StreamedResponse
    {
        $validated = $request->validated();
        $searchId = $validated['search_id'];
        $query = $validated['query'];
        $limit = (int) ($validated['limit'] ?? 20);

        $correlationId = $request->header('X-Correlation-ID');
        if (! is_string($correlationId) || ! Str::isUuid(trim($correlationId))) {
            $correlationId = (string) Str::uuid();
        } else {
            $correlationId = trim($correlationId);
        }

        return response()->stream(function () use ($searchId, $query, $limit, $correlationId): void {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $this->uihClient->streamSearch($searchId, $query, $limit, $correlationId, function (string $chunk): void {
                echo $chunk;

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            });
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'X-Correlation-ID' => $correlationId,
        ]);
    }
}
