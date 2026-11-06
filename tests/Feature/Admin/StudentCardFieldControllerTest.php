<?php

namespace Tests\Feature\Admin;

use App\Models\StudentCardField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentCardFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_manage_student_card_fields(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->get(route('admin.student-card-fields.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_metadata_used_by_the_student_card(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $field = StudentCardField::query()
            ->where('card_section', 'student')
            ->where('field', 'fName')
            ->firstOrFail();
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'ΜΑΡΙΑ',
            'fash_id' => $this->internshipPhase(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.student-card-fields.update', $field), [
                'label' => 'Νέο label ονόματος',
                'help_text' => 'Νέα οδηγία ονόματος',
                'section' => 'Νέα ενότητα',
                'fundings' => ['all'],
                'visible_to_roles' => ['member', 'student'],
                'sort_order' => 1,
                'active' => 1,
            ])
            ->assertRedirect(route('admin.student-card-fields.index'))
            ->assertSessionHas('status', 'Το πεδίο ενημερώθηκε.');

        $this->assertDatabaseHas('fields', [
            'id' => $field->id,
            'label' => 'Νέο label ονόματος',
            'help_text' => 'Νέα οδηγία ονόματος',
        ]);

        $this->actingAs($admin)
            ->get(route('students.show', ['student' => $studentId, 'panel' => 'student']))
            ->assertOk()
            ->assertSee('Νέο label ονόματος')
            ->assertSee('Νέα οδηγία ονόματος');
    }

    public function test_inactive_field_is_hidden_and_cannot_be_updated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $field = StudentCardField::query()
            ->where('card_section', 'student')
            ->where('field', 'fName')
            ->firstOrFail();
        $field->update(['active' => false]);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'ΑΡΧΙΚΟ',
            'fash_id' => $this->internshipPhase(),
        ]);

        $this->actingAs($admin)
            ->get(route('students.show', ['student' => $studentId, 'panel' => 'student']))
            ->assertOk()
            ->assertDontSee('name="fName"', false);

        $this->actingAs($admin)
            ->patch(route('students.section.update', [$studentId, 'student']), ['fName' => 'ΠΑΡΑΚΑΜΨΗ'])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', ['id' => $studentId, 'fName' => 'ΑΡΧΙΚΟ']);
    }
}
