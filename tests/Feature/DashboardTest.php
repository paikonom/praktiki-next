<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_dashboard_with_escaped_name(): void
    {
        $user = User::factory()->create([
            'name' => '<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertDontSee($user->name, false);
        $response->assertSee(route('praktikes.index'));
        $response->assertSee(route('faseis.index'));
    }

    public function test_dashboard_shows_users_department_permissions(): void
    {
        $user = User::factory()->create();
        $departmentId = DB::table('departments')->insertGetId([
            'atlasId' => 123,
            'tmhma' => 'Test Department',
        ]);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'can_manage_espa' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Test Department')
            ->assertSee('ΕΣΠΑ ✓');
    }
}
