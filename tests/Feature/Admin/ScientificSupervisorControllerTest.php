<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScientificSupervisorControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_sees_all_scientific_supervisors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $departmentId = $this->department();
        $this->supervisor($departmentId, 'Παλιός', false);
        $this->supervisor($departmentId, 'Τωρινός', true);

        $this->actingAs($admin)
            ->get(route('admin.scientific-supervisors.index'))
            ->assertOk()
            ->assertSee('Παλιός')
            ->assertSee('Τωρινός')
            ->assertSee('Ενεργός')
            ->assertSee('Ανενεργός');
    }

    public function test_non_admin_cannot_manage_scientific_supervisors(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.scientific-supervisors.index'))
            ->assertForbidden();
    }

    public function test_creating_a_supervisor_activates_it_without_changing_existing_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $departmentId = $this->department();
        $oldSupervisorId = $this->supervisor($departmentId, 'Παλιός', true);
        $studentId = DB::table('students')->insertGetId([
            'department_id' => $departmentId,
            'supervisor_id' => $oldSupervisorId,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.scientific-supervisors.store'), [
                'department_id' => $departmentId,
                'fName' => 'Νέα',
                'lName' => 'Υπεύθυνη',
                'email' => 'new@example.test',
            ])
            ->assertRedirect(route('admin.scientific-supervisors.index'));

        $this->assertDatabaseHas('scientific_supervisors', ['id' => $oldSupervisorId, 'active' => false]);
        $this->assertDatabaseHas('scientific_supervisors', [
            'department_id' => $departmentId,
            'email' => 'new@example.test',
            'active' => true,
        ]);
        $this->assertDatabaseHas('students', ['id' => $studentId, 'supervisor_id' => $oldSupervisorId]);
    }

    public function test_admin_can_reactivate_an_old_supervisor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $departmentId = $this->department();
        $oldSupervisorId = $this->supervisor($departmentId, 'Παλιός', false);
        $currentSupervisorId = $this->supervisor($departmentId, 'Τωρινός', true);

        $this->actingAs($admin)
            ->patch(route('admin.scientific-supervisors.activate', $oldSupervisorId))
            ->assertRedirect(route('admin.scientific-supervisors.index'));

        $this->assertDatabaseHas('scientific_supervisors', ['id' => $oldSupervisorId, 'active' => true]);
        $this->assertDatabaseHas('scientific_supervisors', ['id' => $currentSupervisorId, 'active' => false]);
    }

    public function test_admin_can_edit_details_without_changing_department_or_active_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $departmentId = $this->department();
        $otherDepartmentId = DB::table('departments')->insertGetId(['atlasId' => 401, 'tmhma' => 'Άλλο Τμήμα']);
        $supervisorId = $this->supervisor($departmentId, 'Παλιό', false);

        $this->actingAs($admin)
            ->put(route('admin.scientific-supervisors.update', $supervisorId), [
                'department_id' => $otherDepartmentId,
                'fName' => 'Νέο',
                'lName' => 'Όνομα',
                'email' => 'updated@example.test',
                'thl' => '2101234567',
                'active' => 0,
            ])
            ->assertRedirect(route('admin.scientific-supervisors.index'));

        $this->assertDatabaseHas('scientific_supervisors', [
            'id' => $supervisorId,
            'department_id' => $departmentId,
            'active' => false,
            'fName' => 'Νέο',
            'lName' => 'Όνομα',
            'email' => 'updated@example.test',
            'thl' => '2101234567',
        ]);
    }

    public function test_edit_can_activate_a_supervisor_and_deactivates_the_other_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $departmentId = $this->department();
        $oldSupervisorId = $this->supervisor($departmentId, 'Παλιός', false);
        $currentSupervisorId = $this->supervisor($departmentId, 'Τωρινός', true);

        $this->actingAs($admin)
            ->put(route('admin.scientific-supervisors.update', $oldSupervisorId), [
                'department_id' => $departmentId,
                'fName' => 'Παλιός',
                'lName' => 'Υπεύθυνος',
                'email' => 'old@example.test',
                'active' => 1,
            ])
            ->assertRedirect(route('admin.scientific-supervisors.index'));

        $this->assertDatabaseHas('scientific_supervisors', ['id' => $oldSupervisorId, 'active' => true]);
        $this->assertDatabaseHas('scientific_supervisors', ['id' => $currentSupervisorId, 'active' => false]);
    }

    private function department(): int
    {
        return DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
    }

    private function supervisor(int $departmentId, string $firstName, bool $active): int
    {
        return DB::table('scientific_supervisors')->insertGetId([
            'department_id' => $departmentId,
            'active' => $active,
            'fName' => $firstName,
            'lName' => 'Υπεύθυνος',
            'email' => mb_strtolower($firstName).'@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
