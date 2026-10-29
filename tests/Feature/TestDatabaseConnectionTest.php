<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TestDatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_mysql_test_database_accepts_a_real_persisted_record(): void
    {
        $identity = DB::selectOne('SELECT DATABASE() AS database_name, VERSION() AS version');

        $this->assertSame('temuka_test', $identity->database_name);
        $this->assertStringStartsWith('8.0.40', $identity->version);

        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
