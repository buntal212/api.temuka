<?php

namespace Tests\Unit;

use App\Actions\Engineering\NormalizeExampleValue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NormalizeExampleValueTest extends TestCase
{
    #[DataProvider('values')]
    public function test_it_collapses_whitespace_and_trims_the_result(string $input, string $expected): void
    {
        $normalized = (new NormalizeExampleValue)->execute($input);

        $this->assertSame($expected, $normalized);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function values(): array
    {
        return [
            'single spaces' => ['  Temuka    Marketplace  ', 'Temuka Marketplace'],
            'tabs and newlines' => ["\tTemuka\n\nMarketplace\r\n", 'Temuka Marketplace'],
            'unicode separators' => ["\u{00A0}Temuka\u{2003}Marketplace\u{00A0}", 'Temuka Marketplace'],
            'whitespace only' => [" \t\n ", ''],
        ];
    }
}
