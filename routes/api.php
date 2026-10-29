<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'application' => 'temuka',
        'version' => 'v1',
    ]))->name('api.v1.health');
});
