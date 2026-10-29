<?php

namespace Tests\Unit;

use App\Support\TestDatabaseSafety;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TestDatabaseSafetyTest extends TestCase
{
    public function test_approved_test_configuration_is_accepted(): void
    {
        TestDatabaseSafety::assertConfigurationIsSafe($this->safeConfiguration());

        $this->assertTrue(true);
    }

    #[DataProvider('unsafeConfigurations')]
    public function test_unsafe_configuration_is_rejected(array $changes): void
    {
        $this->expectException(RuntimeException::class);

        TestDatabaseSafety::assertConfigurationIsSafe(array_merge($this->safeConfiguration(), $changes));
    }

    public static function unsafeConfigurations(): array
    {
        return [
            'wrong environment' => [['app_env' => 'local']],
            'wrong default connection' => [['default_connection' => 'sqlite']],
            'wrong database driver' => [['connection_driver' => 'sqlite']],
            'unapproved hostname' => [['host' => '127.0.0.1']],
            'wrong port' => [['port' => '3307']],
            'wrong database name' => [['database' => 'temuka_dev']],
            'wrong database user' => [['username' => 'temuka_local']],
            'database URL override' => [['url' => 'mysql://example.invalid/temuka_test']],
            'empty password' => [['password' => '']],
        ];
    }

    private function safeConfiguration(): array
    {
        return [
            'app_env' => 'testing',
            'default_connection' => 'mysql',
            'connection_driver' => 'mysql',
            'host' => 'test-db',
            'port' => '3306',
            'database' => 'temuka_test',
            'username' => 'temuka_test',
            'password' => 'test-only-password',
            'url' => '',
        ];
    }
}
