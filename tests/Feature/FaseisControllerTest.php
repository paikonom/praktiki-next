<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaseisControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_sees_only_phases_from_accessible_departments(): void
    {
        $user = User::factory()->create();
        $visibleDepartment = DB::table('departments')->insertGetId(['atlasId' => 1, 'tmhma' => 'Visible Department']);
        $emptyDepartment = DB::table('departments')->insertGetId(['atlasId' => 3, 'tmhma' => 'Empty Department']);
        $hiddenDepartment = DB::table('departments')->insertGetId(['atlasId' => 2, 'tmhma' => 'Hidden Department']);
        DB::table('user_department_access')->insert([
            ['user_id' => $user->id, 'department_id' => $visibleDepartment],
            ['user_id' => $user->id, 'department_id' => $emptyDepartment],
        ]);

        $fundingId = DB::table('fundings')->where('code', 'Espa')->value('id');
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => $fundingId,
            'praxh_year' => '2025-2026',
        ]);

        $visiblePhase = DB::table('praktiki_phases')->insertGetId([
            'legacy_fid' => 1,
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $visibleDepartment,
            'name' => 'Α',
            'details' => json_encode(['apofash_tmhmatos' => '12-05-2025']),
        ]);
        DB::table('praktiki_phases')->insert([
            'legacy_fid' => 2,
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $hiddenDepartment,
            'name' => 'Α',
        ]);
        $this->actingAs($user)
            ->get(route('faseis.index'))
            ->assertOk()
            ->assertSee('Visible Department')
            ->assertSee('12-05-2025')
            ->assertSee('Empty Department')
            ->assertSee('Νέα Φάση')
            ->assertSee('Δεν βρέθηκαν φάσεις για αυτό το τμήμα.')
            ->assertDontSee('Hidden Department');
    }

    public function test_user_can_create_a_phase_for_a_department_they_manage(): void
    {
        $user = User::factory()->create();
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'can_manage_institution' => true,
        ]);
        $fundingId = DB::table('fundings')->where('code', 'Institution')->value('id');
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => $fundingId,
            'praxh_year' => '2027-2028',
        ]);

        $response = $this->actingAs($user)->post(route('faseis.store'), [
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $departmentId,
            'name' => 'Α',
            'apofash_ELKE' => '150/2027',
            'dates' => 'Έναρξη μετά την 01/06/2027',
            'notes' => 'Κοινή σημείωση Institution',
        ]);

        $response->assertRedirect(route('faseis.index', [
            'year' => '2027-2028',
            'funding' => 'Institution',
        ]));
        $this->assertDatabaseHas('praktiki_phases', [
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $departmentId,
            'name' => 'Α',
            'notes' => 'Κοινή σημείωση Institution',
        ]);
        $details = json_decode(DB::table('praktiki_phases')->where('department_id', $departmentId)->value('details'), true);
        $this->assertSame('150/2027', $details['apofash_ELKE']);
    }

    public function test_user_can_update_a_phase_they_manage(): void
    {
        $user = User::factory()->create();
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'can_manage_espa' => true,
        ]);
        $fundingId = DB::table('fundings')->where('code', 'Espa')->value('id');
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => $fundingId,
            'praxh_year' => '2026-2027',
        ]);
        $phaseId = DB::table('praktiki_phases')->insertGetId([
            'legacy_fid' => 500,
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $departmentId,
            'name' => 'A',
        ]);
        $response = $this->actingAs($user)->put(route('faseis.update', $phaseId), [
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $departmentId,
            'name' => 'Β',
            'apofash_tmhmatos' => '20-05-2027',
            'ADA_apofash_ELKE' => 'ABC123',
        ]);

        $response->assertRedirect(route('faseis.index', [
            'year' => '2026-2027',
            'funding' => 'Espa',
        ]));
        $this->assertDatabaseHas('praktiki_phases', ['id' => $phaseId, 'name' => 'Β']);
        $details = json_decode(DB::table('praktiki_phases')->where('id', $phaseId)->value('details'), true);
        $this->assertSame('20-05-2027', $details['apofash_tmhmatos']);
        $this->assertSame('ABC123', $details['ADA_apofash_ELKE']);
    }

    public function test_user_cannot_edit_a_phase_they_do_not_manage(): void
    {
        $user = User::factory()->create();
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'can_manage_espa' => false,
        ]);
        $fundingId = DB::table('fundings')->where('code', 'Espa')->value('id');
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => $fundingId,
            'praxh_year' => '2026-2027',
        ]);
        $phaseId = DB::table('praktiki_phases')->insertGetId([
            'legacy_fid' => 501,
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $departmentId,
            'name' => 'A',
        ]);

        $this->actingAs($user)
            ->get(route('faseis.edit', $phaseId))
            ->assertForbidden();
    }

    public function test_admin_can_see_and_edit_all_phases_without_department_access_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        $fundingId = DB::table('fundings')->where('code', 'Espa')->value('id');
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => $fundingId,
            'praxh_year' => '2026-2027',
        ]);
        $phaseId = DB::table('praktiki_phases')->insertGetId([
            'legacy_fid' => 502,
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $departmentId,
            'name' => 'A',
        ]);
        $this->actingAs($admin)
            ->get(route('faseis.index'))
            ->assertOk()
            ->assertSee('Πληροφορική')
            ->assertSee('Επεξεργασία Φάσης');

        $this->actingAs($admin)
            ->get(route('faseis.edit', $phaseId))
            ->assertOk();
    }

    public function test_view_only_user_cannot_edit_a_phase_they_manage(): void
    {
        $user = User::factory()->create(['view_only' => true]);
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'can_manage_espa' => true,
        ]);
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => DB::table('fundings')->where('code', 'Espa')->value('id'),
            'praxh_year' => '2026-2027',
        ]);
        $phaseId = DB::table('praktiki_phases')->insertGetId([
            'legacy_fid' => 503,
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $departmentId,
            'name' => 'Α',
        ]);

        $this->actingAs($user)->get(route('faseis.edit', $phaseId))->assertForbidden();
    }
}
