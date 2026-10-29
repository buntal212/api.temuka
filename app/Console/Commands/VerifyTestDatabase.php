<?php

namespace App\Console\Commands;

use App\Support\TestDatabaseSafety;
use Illuminate\Console\Command;
use Throwable;

class VerifyTestDatabase extends Command
{
    protected $signature = 'temuka:verify-test-database';

    protected $description = 'Fail closed unless the active connection is the isolated MySQL test database';

    public function handle(): int
    {
        try {
            TestDatabaseSafety::assertSafe();
        } catch (Throwable $exception) {
            $this->error('Test database safety check failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Verified isolated MySQL 8.0.40 test database temuka_test.');

        return self::SUCCESS;
    }
}
