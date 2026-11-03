<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CatalogSearchRequest;
use App\Services\UIH\UihStreamClient;
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

        return response()->stream(function () use ($searchId, $query, $limit): void {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $this->uihClient->streamSearch($searchId, $query, $limit, function (string $chunk): void {
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
        ]);
    }
}
