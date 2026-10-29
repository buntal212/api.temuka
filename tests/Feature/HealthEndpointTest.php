<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_the_versioned_json_contract(): void
    {
        $this->get('/api/v1/health')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'application' => 'temuka',
                'version' => 'v1',
            ]);
    }

    public function test_health_endpoint_is_registered_on_the_versioned_api_route(): void
    {
        $this->assertSame('/api/v1/health', route('api.v1.health', absolute: false));
    }
}
