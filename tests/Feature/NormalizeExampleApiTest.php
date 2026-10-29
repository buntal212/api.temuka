<?php

namespace Tests\Feature;

use Tests\TestCase;

class NormalizeExampleApiTest extends TestCase
{
    public function test_it_returns_the_versioned_json_resource_and_preserves_the_original_value(): void
    {
        $original = " \tTemuka  \n Marketplace  ";

        $this->postJson('/api/v1/examples/normalize', ['value' => $original])
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertExactJson([
                'data' => [
                    'original' => $original,
                    'normalized' => 'Temuka Marketplace',
                ],
            ]);
    }

    public function test_it_normalizes_tabs_newlines_and_unicode_whitespace(): void
    {
        $this->postJson('/api/v1/examples/normalize', [
            'value' => "\u{00A0}Temuka\t\n\u{2003}Marketplace\u{00A0}",
        ])->assertOk()->assertJsonPath('data.normalized', 'Temuka Marketplace');
    }

    public function test_it_rejects_empty_and_whitespace_only_values(): void
    {
        foreach (['', " \t\n "] as $value) {
            $this->postJson('/api/v1/examples/normalize', ['value' => $value])
                ->assertUnprocessable()
                ->assertJsonPath('errors.value.0', 'Field value wajib diisi.');
        }
    }

    public function test_it_rejects_a_missing_value(): void
    {
        $this->postJson('/api/v1/examples/normalize', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.value.0', 'Field value wajib diisi.');
    }

    public function test_it_rejects_a_non_string_value(): void
    {
        $this->postJson('/api/v1/examples/normalize', ['value' => ['Temuka']])
            ->assertUnprocessable()
            ->assertJsonPath('errors.value.0', 'Field value harus berupa teks.');
    }

    public function test_it_rejects_values_longer_than_one_thousand_characters(): void
    {
        $this->postJson('/api/v1/examples/normalize', ['value' => str_repeat('a', 1001)])
            ->assertUnprocessable()
            ->assertJsonPath('errors.value.0', 'Field value maksimal 1000 karakter.');
    }

    public function test_it_rejects_unsupported_input_fields(): void
    {
        $this->postJson('/api/v1/examples/normalize', [
            'value' => 'Temuka',
            'debug' => true,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.debug.0', 'Field debug tidak didukung.');
    }

    public function test_it_is_available_in_local_and_testing_environments(): void
    {
        config(['app.env' => 'local']);

        $this->postJson('/api/v1/examples/normalize', ['value' => 'Temuka'])
            ->assertOk();

        config(['app.env' => 'testing']);

        $this->postJson('/api/v1/examples/normalize', ['value' => 'Temuka'])
            ->assertOk();
    }

    public function test_it_returns_not_found_in_production(): void
    {
        config(['app.env' => 'production']);

        $this->postJson('/api/v1/examples/normalize', ['value' => 'Temuka'])
            ->assertNotFound()
            ->assertJsonPath('message', 'Not Found.');
    }
}
