<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentImportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_handler_sees_only_phases_that_can_be_managed(): void
    {
        $user = User::factory()->create();
        $visibleDepartment = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Ορατό Τμήμα']);
        $hiddenDepartment = DB::table('departments')->insertGetId(['atlasId' => 401, 'tmhma' => 'Κρυφό Τμήμα']);
        $visiblePhase = $this->internshipPhase('Espa', '2025-2026', $visibleDepartment);
        $this->internshipPhase('Espa', '2025-2026', $hiddenDepartment);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $visibleDepartment,
            'can_manage_espa' => true,
        ]);

        $this->actingAs($user)
            ->get(route('students.import.create'))
            ->assertOk()
            ->assertSee('Ορατό Τμήμα')
            ->assertSee('value="'.$visiblePhase.'"', false)
            ->assertDontSee('Κρυφό Τμήμα');
    }

    public function test_check_reports_valid_and_invalid_rows_without_writing_to_database(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        $phaseId = $this->internshipPhase('Espa', '2025-2026', $departmentId);
        $this->createScientificSupervisor($departmentId);
        $studentCount = DB::table('students')->count();

        $this->actingAs($user)
            ->post(route('students.import.check'), [
                'department_id' => $departmentId,
                'phase_id' => $phaseId,
                'students' => implode("\n", [
                    "1234567890123\t6912345678\tstudent@example.test\tΜαρία Καθηγήτρια",
                    "123\tόχι τηλέφωνο\tλάθος-email\t",
                ]),
            ])
            ->assertOk()
            ->assertSee('1 σωστές, 1 με πρόβλημα')
            ->assertSee('Έτοιμη')
            ->assertSee('Ο ΑΜ πρέπει να έχει 13 ψηφία.')
            ->assertSee('Λείπει ο επόπτης καθηγητής.');

        $this->assertSame($studentCount, DB::table('students')->count());
    }

    public function test_check_reports_an_existing_active_student_in_the_same_phase(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        $phaseId = $this->internshipPhase('Espa', '2025-2026', $departmentId);
        DB::table('students')->insertGetId([
            'department_id' => $departmentId,
            'fash_id' => $phaseId,
            'AMF' => '1234567890123',
        ]);

        $this->actingAs($user)
            ->post(route('students.import.check'), [
                'department_id' => $departmentId,
                'phase_id' => $phaseId,
                'students' => "1234567890123\t6912345678\tstudent@example.test\tΜαρία Καθηγήτρια",
            ])
            ->assertOk()
            ->assertSee('Υπάρχει ήδη ενεργή εγγραφή του ΑΜ σε αυτή τη φάση.');
    }

    public function test_handler_cannot_check_a_phase_without_management_permission(): void
    {
        $user = User::factory()->create();
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        $phaseId = $this->internshipPhase('Espa', '2025-2026', $departmentId);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'can_manage_espa' => false,
        ]);

        $this->actingAs($user)
            ->post(route('students.import.check'), [
                'department_id' => $departmentId,
                'phase_id' => $phaseId,
                'students' => "1234567890123\t6912345678\tstudent@example.test\tΜαρία Καθηγήτρια",
            ])
            ->assertForbidden();
    }

    public function test_valid_checked_rows_are_inserted_with_their_related_records(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        $phaseId = $this->internshipPhase('Espa', '2025-2026', $departmentId);
        $supervisorId = $this->createScientificSupervisor($departmentId);

        $this->actingAs($user)
            ->post(route('students.import.store'), [
                'department_id' => $departmentId,
                'phase_id' => $phaseId,
                'students' => implode("\n", [
                    "1234567890123\t6912345678\tfirst@example.test\tΜαρία Καθηγήτρια",
                    "1234567890124\t6912345679\tsecond@example.test\tΝίκος Καθηγητής",
                ]),
            ])
            ->assertRedirect(route('students.index', [
                'year' => '2025-2026',
                'funding' => 'Espa',
                'department_id' => $departmentId,
                'phase' => 'Α',
            ]))
            ->assertSessionHas('status', 'Εισήχθησαν επιτυχώς 2 φοιτητές.');

        $students = DB::table('students')->whereIn('AMF', ['1234567890123', '1234567890124'])->get();

        $this->assertCount(2, $students);
        $this->assertSame([$supervisorId], $students->pluck('supervisor_id')->unique()->values()->all());
        $this->assertSame([1], $students->pluck('doseis_count')->unique()->values()->all());
        $this->assertSame(0, DB::table('student_actions')->whereIn('student_id', $students->pluck('id'))->count());
        $this->assertSame(2, DB::table('providers')->whereIn('s_id', $students->pluck('id'))->count());
        $this->assertSame(2, $students->pluck('provider_access_token_hash')->unique()->count());
    }

    public function test_import_is_all_or_nothing_when_one_row_is_invalid(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        $phaseId = $this->internshipPhase('Espa', '2025-2026', $departmentId);
        $this->createScientificSupervisor($departmentId);

        $this->actingAs($user)
            ->from(route('students.import.create'))
            ->post(route('students.import.store'), [
                'department_id' => $departmentId,
                'phase_id' => $phaseId,
                'students' => implode("\n", [
                    "1234567890123\t6912345678\tfirst@example.test\tΜαρία Καθηγήτρια",
                    "123\t6912345679\tsecond@example.test\tΝίκος Καθηγητής",
                ]),
            ])
            ->assertRedirect(route('students.import.create'))
            ->assertSessionHasErrors('students');

        $this->assertDatabaseMissing('students', ['AMF' => '1234567890123']);
        $this->assertDatabaseMissing('students', ['AMF' => '123']);
    }

    private function createScientificSupervisor(int $departmentId): int
    {
        return DB::table('scientific_supervisors')->insertGetId([
            'department_id' => $departmentId,
            'fName' => 'Μαρία',
            'lName' => 'Υπεύθυνη',
            'email' => 'supervisor@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
