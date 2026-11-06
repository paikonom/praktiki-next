<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportLegacyDepartmentAccessCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_import_stops_when_destination_contains_access(): void
    {
        $user = User::factory()->create();
        $departmentId = DB::table('departments')->insertGetId([
            'atlasId' => 1,
            'tmhma' => 'Test',
        ]);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $departmentId,
        ]);

        $this->artisan('legacy:import-department-access')
            ->expectsOutput('The destination access table is not empty. Nothing was imported.')
            ->assertFailed();

        $this->assertDatabaseCount('user_department_access', 1);
    }
}
