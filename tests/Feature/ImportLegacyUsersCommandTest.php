<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ImportLegacyUsersCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_import_stops_when_destination_contains_a_user(): void
    {
        User::factory()->create();

        $this->artisan('legacy:import-users')
            ->expectsOutput('The destination users table is not empty. Nothing was imported.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }
}
