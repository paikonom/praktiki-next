<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DepartmentAccessControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.department-access'))
            ->assertRedirect(route('login'));
    }

    public function test_member_cannot_view_department_access(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->get(route('admin.department-access'))
            ->assertForbidden();
    }

    public function test_admin_can_view_department_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'Test User', 'username' => 'test-user']);
        $secondUser = User::factory()->create(['name' => 'Second User']);
        $departmentId = DB::table('departments')->insertGetId([
            'atlasId' => 123,
            'tmhma' => 'Test Department',
        ]);
        DB::table('user_department_access')->insert([
            ['user_id' => $user->id, 'department_id' => $departmentId, 'can_manage_espa' => true],
            ['user_id' => $secondUser->id, 'department_id' => $departmentId, 'can_manage_espa' => true],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.department-access'))
            ->assertOk()
            ->assertSee('Test Department')
            ->assertSee('Test User')
            ->assertSee('test-user')
            ->assertSee('ΕΣΠΑ')
            ->assertSee('Προσοχή: περισσότεροι από έναν χειριστές για ΕΣΠΑ.');
    }

    public function test_admin_can_replace_department_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();
        $departmentId = DB::table('departments')->insertGetId([
            'atlasId' => 123,
            'tmhma' => 'Test Department',
        ]);
        DB::table('user_department_access')->insert([
            'user_id' => $oldUser->id,
            'department_id' => $departmentId,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.department-access.update', $departmentId), [
                'user_ids' => [$newUser->id],
                'manage_espa' => [$newUser->id],
                'manage_internship_provider' => [$newUser->id],
            ])
            ->assertRedirect(route('admin.department-access'))
            ->assertSessionHas('status', 'Η πρόσβαση του τμήματος ενημερώθηκε.');

        $this->assertDatabaseMissing('user_department_access', [
            'user_id' => $oldUser->id,
            'department_id' => $departmentId,
        ]);
        $this->assertDatabaseHas('user_department_access', [
            'user_id' => $newUser->id,
            'department_id' => $departmentId,
            'can_manage_espa' => true,
            'can_manage_institution' => false,
            'can_manage_internship_provider' => true,
        ]);
    }
}
