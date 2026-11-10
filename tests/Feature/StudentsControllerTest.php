<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentAction;
use App\Models\StudentApografiko;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Services\StudentEmailService;
use App\Support\StudentWorkflow;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_sees_only_students_from_accessible_departments(): void
    {
        $user = User::factory()->create();
        $visibleDepartment = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        $hiddenDepartment = DB::table('departments')->insertGetId(['atlasId' => 401, 'tmhma' => 'Κρυφό Τμήμα']);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $visibleDepartment,
        ]);

        $fundingId = DB::table('fundings')->where('code', 'Espa')->value('id');
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => $fundingId,
            'praxh_year' => '2025-2026',
        ]);
        $visiblePhase = DB::table('praktiki_phases')->insertGetId([
            'legacy_fid' => 1001,
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $visibleDepartment,
            'name' => 'Α',
        ]);
        $hiddenPhase = DB::table('praktiki_phases')->insertGetId([
            'legacy_fid' => 1002,
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $hiddenDepartment,
            'name' => 'Α',
        ]);
        DB::table('students')->insert([
            [
                'department_id' => $visibleDepartment,
                'fash_id' => $visiblePhase,
                'fName' => 'Ορατός',
                'lName' => 'Φοιτητής',
                'AMF' => '111',
            ],
            [
                'department_id' => $hiddenDepartment,
                'fash_id' => $hiddenPhase,
                'fName' => 'Κρυφός',
                'lName' => 'Φοιτητής',
                'AMF' => '222',
            ],
        ]);
        $visibleStudentId = DB::table('students')->where('AMF', '111')->value('id');
        $this->approveStudentDetails($visibleStudentId);
        StudentApografiko::insert([
            ['student_id' => $visibleStudentId, 'type' => 'input', 'status' => StudentApografiko::STATUS_COMPLETED],
            ['student_id' => $visibleStudentId, 'type' => 'output', 'status' => StudentApografiko::STATUS_COMPLETED],
        ]);

        $visibleStudent = Student::with(['actions', 'documents.type', 'apografika', 'questionnaire', 'providerQuestionnaire'])
            ->findOrFail($visibleStudentId);
        $nextSection = WorkflowAction::nextIncompleteSectionFor(
            $visibleStudent,
            WorkflowAction::activeForFunding('Espa'),
        );
        $this->assertSame('A', $nextSection['section']);
        $this->assertCount(6, $nextSection['pendingActions']);

        $this->actingAs($user)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Ορατός')
            ->assertSee('Πληροφορική')
            ->assertSee('ΦΤ')
            ->assertSee('ΦΡ')
            ->assertSee('ΠΑ')
            ->assertSee('Όλα τα έτη')
            ->assertSee('Όλες οι χρηματοδοτήσεις')
            ->assertSee('Εκκρεμότητες')
            ->assertSee('Ενότητα Α (6)')
            ->assertDontSee('Κρυφός')
            ->assertDontSee('Κρυφό Τμήμα');

        $this->actingAs($user)
            ->get(route('students.index', ['year' => 'all', 'funding' => 'all']))
            ->assertOk()
            ->assertSee('Ορατός')
            ->assertDontSee('Κρυφός');
    }

    public function test_guest_cannot_view_students(): void
    {
        $this->get(route('students.index'))->assertRedirect(route('login'));
    }

    public function test_student_card_returns_to_the_last_filtered_students_list(): void
    {
        $user = User::factory()->create();
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        DB::table('user_department_access')->insert(['user_id' => $user->id, 'department_id' => $departmentId]);
        $phaseId = $this->internshipPhase('Espa', '2025-2026', $departmentId);
        $studentId = DB::table('students')->insertGetId([
            'department_id' => $departmentId,
            'fash_id' => $phaseId,
            'fName' => 'ΔΟΚΙΜΗ',
            'lName' => 'ΦΟΙΤΗΤΗΣ',
        ]);

        $returnUrl = '/students?year=2025-2026&funding=Espa&department_id='.$departmentId.'&phase=%CE%91';

        $this->actingAs($user)->get($returnUrl)->assertOk();
        $this->actingAs($user)
            ->get(route('students.show', $studentId))
            ->assertOk()
            ->assertSee($returnUrl);
    }

    public function test_user_can_view_card_only_for_student_in_accessible_department(): void
    {
        $user = User::factory()->create();
        $visibleDepartment = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        $hiddenDepartment = DB::table('departments')->insertGetId(['atlasId' => 401, 'tmhma' => 'Κρυφό Τμήμα']);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $visibleDepartment,
        ]);

        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => DB::table('fundings')->where('code', 'Espa')->value('id'),
            'praxh_year' => '2025-2026',
        ]);
        $phaseId = DB::table('praktiki_phases')->insertGetId([
            'legacy_fid' => 2001,
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $visibleDepartment,
            'name' => 'Α',
            'details' => json_encode(['apofash_tmhmatos' => 'ΑΠΟΦΑΣΗ TEST']),
        ]);
        $supervisorId = DB::table('scientific_supervisors')->insertGetId([
            'department_id' => $visibleDepartment,
            'fName' => 'ΜΑΡΙΑ',
            'lName' => 'ΔΟΚΙΜΑΣΤΙΚΗ',
            'email' => 'supervisor@example.test',
        ]);

        $visibleStudentId = DB::table('students')->insertGetId([
            'department_id' => $visibleDepartment,
            'fash_id' => $phaseId,
            'supervisor_id' => $supervisorId,
            'fName' => 'Ορατός',
            'lName' => 'Φοιτητής',
        ]);
        $hiddenStudentId = DB::table('students')->insertGetId([
            'department_id' => $hiddenDepartment,
            'fName' => 'Κρυφός',
            'lName' => 'Φοιτητής',
        ]);
        DB::table('providers')->insert(['s_id' => $visibleStudentId, 'f_epwnymia' => 'ΔΟΚΙΜΑΣΤΙΚΟΣ ΦΟΡΕΑΣ']);
        DB::table('student_actions')->insert([
            'student_id' => $visibleStudentId,
            'action' => 'stoix_student',
            'status' => 'submitted',
        ]);

        $this->actingAs($user)
            ->get(route('students.show', $visibleStudentId))
            ->assertOk()
            ->assertSee('Ορατός')
            ->assertSee('ΔΟΚΙΜΑΣΤΙΚΟΣ ΦΟΡΕΑΣ')
            ->assertSee('ΜΑΡΙΑ ΔΟΚΙΜΑΣΤΙΚΗ')
            ->assertSee('Εισαγωγή στοιχείων φοιτητή');

        $this->actingAs($user)
            ->get(route('students.show', ['student' => $visibleStudentId, 'panel' => 'office']))
            ->assertOk()
            ->assertSee('Κατάσταση αιτήματος πληρωμής')
            ->assertDontSee('Λείπουν 7 στοιχεία.');

        $this->actingAs($user)
            ->get(route('students.show', ['student' => $visibleStudentId, 'panel' => 'agreement']))
            ->assertOk()
            ->assertSee('Αρχικό συμφωνητικό')
            ->assertSee('Προεπισκόπηση PDF');

        $this->actingAs($user)
            ->get(route('students.show', $hiddenStudentId))
            ->assertNotFound();
    }

    public function test_student_calculates_duration_including_interruption(): void
    {
        $student = (new Student)->forceFill([
            'startDate' => '2026-01-01',
            'endDate' => '2026-01-20',
            'diakoptomeno' => true,
            'endDate_DKPT_A' => '2026-01-05',
            'startDate_DKPT_B' => '2026-01-16',
        ]);

        $this->assertSame(10, $student->duration_days);
    }

    public function test_old_students_are_marked_as_legacy(): void
    {
        $student = (new Student)->forceFill(['praxh_year' => '2023-2024']);

        $this->assertTrue($student->isLegacy());
        $this->assertSame('legacy', StudentWorkflow::currentState($student));

        $newStudent = (new Student)->forceFill(['praxh_year' => '2024-2025']);
        $this->assertFalse($newStudent->isLegacy());
    }

    public function test_student_uses_funding_code_selected_for_a_list_without_loading_the_phase(): void
    {
        $student = (new Student)->forceFill([
            'funding_code' => 'Espa',
            'startDate' => now()->subDay(),
        ]);

        $this->assertFalse($student->relationLoaded('phase'));
        $this->assertSame('Espa', $student->fundingCode());
        $this->assertNull(app(StudentEmailService::class)->unavailableReason($student, 'input'));
    }

    public function test_card_workflow_uses_legacy_labels_and_hides_future_steps(): void
    {
        $student = (new Student)->forceFill([
            'startDate' => now()->addMonth(),
            'endDate' => now()->addMonths(3),
        ]);

        $workflow = StudentWorkflow::for($student, 'Espa');

        $this->assertNotEmpty($workflow['before']);
        $this->assertEmpty($workflow['during']);
        $this->assertEmpty($workflow['after']);

        $student->startDate = now()->subMonths(3);
        $student->endDate = now()->subMonth();
        $student->setRelation('actions', collect([
            new StudentAction(['action' => 'aitima_plhrwmhs', 'status' => 'approved']),
        ]));
        $workflow = StudentWorkflow::for($student, 'Espa');

        $this->assertContains(
            'Το ΓΠΑ δήλωσε οτι έγινε η διαδικασία πληρωμής;',
            collect($workflow['after'])->pluck('label')->all(),
        );
    }

    public function test_user_can_update_student_and_provider_sections_for_accessible_department(): void
    {
        $user = User::factory()->create();
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        DB::table('user_department_access')->insert(['user_id' => $user->id, 'department_id' => $departmentId]);
        $studentId = DB::table('students')->insertGetId([
            'department_id' => $departmentId,
            'fName' => 'Παλιό',
            'address_odos_polh' => 'ΠΑΛΙΑ ΠΟΛΗ',
        ]);
        DB::table('providers')->insert([
            's_id' => $studentId,
            'f_perioxh' => 'ΠΑΛΙΑ ΕΔΡΑ',
            'f_topos_ekt_prakt' => 'ΠΑΛΙΟΣ ΤΟΠΟΣ',
        ]);

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), [
                'fName' => 'Νέο',
                'questionnaire_version' => 1,
            ])
            ->assertRedirect(route('students.show', ['student' => $studentId, 'panel' => 'student']));

        $this->actingAs($user)
            ->get(route('students.show', ['student' => $studentId, 'panel' => 'student']))
            ->assertOk()
            ->assertSee('ΕΚΔΟΣΗ ΕΡΩΤΗΜΑΤΟΛΟΓΙΟΥ')
            ->assertSee('>2</div>', false);

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'provider']), ['f_epwnymia' => 'Νέος Φορέας'])
            ->assertRedirect(route('students.show', ['student' => $studentId, 'panel' => 'provider']));

        $this->actingAs($user)->patch(route('students.section.update', [$studentId, 'student']), [
            'address_odos_polh' => 'ΝΕΑ ΠΟΛΗ',
        ]);
        $this->actingAs($user)->patch(route('students.section.update', [$studentId, 'provider']), [
            'f_perioxh' => 'ΝΕΑ ΕΔΡΑ',
            'f_topos_ekt_prakt' => 'ΝΕΟΣ ΤΟΠΟΣ',
        ]);

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'office']), ['stoix_student' => 'approved'])
            ->assertRedirect(route('students.show', ['student' => $studentId, 'panel' => 'office']));

        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'fName' => 'Νέο',
            'questionnaire_version' => 2,
            'address_odos_polh' => 'ΠΑΛΙΑ ΠΟΛΗ',
        ]);
        $this->assertDatabaseHas('providers', [
            's_id' => $studentId,
            'f_epwnymia' => 'Νέος Φορέας',
            'f_perioxh' => 'ΠΑΛΙΑ ΕΔΡΑ',
            'f_topos_ekt_prakt' => 'ΠΑΛΙΟΣ ΤΟΠΟΣ',
        ]);
        $this->assertDatabaseHas('student_actions', [
            'student_id' => $studentId,
            'action' => 'stoix_student',
            'status' => 'approved',
        ]);
    }

    public function test_student_section_rejects_invalid_values(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Αρχικό',
            'startDate' => '2026-01-01',
        ]);

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), ['startDate' => 'όχι ημερομηνία'])
            ->assertSessionHasErrors('startDate');

        $this->assertDatabaseHas('students', ['id' => $studentId, 'startDate' => '2026-01-01']);
    }

    public function test_office_section_accepts_only_known_status_values(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Δοκιμή',
        ]);

        $this->actingAs($user)
            ->post(route('students.workflow-actions.store', [$studentId, 'aitima_plhrwmhs']), ['status' => 'invalid'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('student_actions', ['student_id' => $studentId, 'action' => 'aitima_plhrwmhs']);
    }

    public function test_office_section_ignores_unselected_action_statuses(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Δοκιμή',
        ]);

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'office']), [
                'finish_pa' => '',
                'aitima_plhrwmhs' => '',
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('status', 'Δεν υπήρξε κάποια αλλαγή για αποθήκευση.');

        $this->assertDatabaseMissing('student_actions', ['student_id' => $studentId]);
    }

    public function test_internship_status_comes_from_the_workflow_action(): void
    {
        $student = Student::forceCreate(['fName' => 'Δοκιμή']);
        $student->actions()->create(['action' => 'katastash_praktikis', 'status' => 'cancelled']);

        $this->assertTrue($student->isCancelled());
        $this->assertSame('cancelled', $student->internshipStatus());
    }

    public function test_update_message_lists_changed_fields_and_reports_when_nothing_changed(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Παλιό',
        ]);

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), ['fName' => 'Νέο'])
            ->assertSessionHas('status', 'Αποθηκεύτηκαν οι αλλαγές: ΟΝΟΜΑ.');

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), ['fName' => 'Νέο'])
            ->assertSessionHas('status', 'Δεν υπήρξε κάποια αλλαγή για αποθήκευση.')
            ->assertSessionHas('status_type', 'info');
    }

    public function test_diakoptomeno_accepts_only_the_legacy_zero_or_one_values(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Δοκιμή',
            'diakoptomeno' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('students.show', ['student' => $studentId, 'panel' => 'student']))
            ->assertOk()
            ->assertSee('<option value="0" selected>ΟΧΙ</option>', false)
            ->assertDontSee('0 (παλιά τιμή)');

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), ['diakoptomeno' => ''])
            ->assertSessionHasErrors('diakoptomeno');

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), ['diakoptomeno' => 1])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', ['id' => $studentId, 'diakoptomeno' => 1]);
    }

    public function test_decimal_months_match_the_configured_dropdown_value(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Δοκιμή',
            'numMhnes' => '3.00',
        ]);

        $this->actingAs($user)
            ->get(route('students.show', ['student' => $studentId, 'panel' => 'student']))
            ->assertOk()
            ->assertSee('<option value="3" selected>3</option>', false)
            ->assertDontSee('3.00 (παλιά τιμή)');

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), ['numMhnes' => '3.00'])
            ->assertSessionDoesntHaveErrors();
    }

    public function test_student_identifiers_reject_invalid_formats(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Δοκιμή',
        ]);

        foreach ([
            'afm' => '12345678A',
            'amka' => '1234567890A',
            'AMF' => '123456789012A',
            'address_tk' => '1234A',
            'ama' => '123456A',
            'tel' => '6812345678',
            'iban' => 'GR123',
        ] as $field => $invalidValue) {
            $this->actingAs($user)
                ->patch(route('students.section.update', [$studentId, 'student']), [$field => $invalidValue])
                ->assertSessionHasErrors($field);
        }
    }

    public function test_provider_identifiers_reject_invalid_formats(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Δοκιμή',
        ]);

        foreach (['f_afm' => '12345678A', 'f_tk' => '1234A'] as $field => $invalidValue) {
            $this->actingAs($user)
                ->patch(route('students.section.update', [$studentId, 'provider']), [$field => $invalidValue])
                ->assertSessionHasErrors($field);
        }
    }

    public function test_unchanged_legacy_value_does_not_block_another_update(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Παλιό',
            'AMF' => 'LEGACY',
        ]);

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), [
                'fName' => 'Νέο',
                'AMF' => 'LEGACY',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', ['id' => $studentId, 'fName' => 'Νέο', 'AMF' => 'LEGACY']);
    }

    public function test_user_cannot_update_student_from_inaccessible_department(): void
    {
        $user = User::factory()->create();
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 401, 'tmhma' => 'Κρυφό Τμήμα']);
        $studentId = DB::table('students')->insertGetId([
            'department_id' => $departmentId,
            'fName' => 'Αρχικό',
        ]);

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), ['fName' => 'Παράνομη αλλαγή'])
            ->assertForbidden();

        $this->assertDatabaseHas('students', ['id' => $studentId, 'fName' => 'Αρχικό']);
    }

    public function test_view_only_user_can_view_but_cannot_update_student(): void
    {
        $user = User::factory()->create(['view_only' => true]);
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 330, 'tmhma' => 'Πληροφορική']);
        DB::table('user_department_access')->insert(['user_id' => $user->id, 'department_id' => $departmentId]);
        $studentId = DB::table('students')->insertGetId([
            'department_id' => $departmentId,
            'fName' => 'Αρχικό',
        ]);

        $this->actingAs($user)->get(route('students.show', $studentId))->assertOk();
        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), ['fName' => 'Αλλαγή'])
            ->assertForbidden();

        $this->assertDatabaseHas('students', ['id' => $studentId, 'fName' => 'Αρχικό']);
    }

    public function test_rescom_fields_use_catalogs_and_validate_the_saved_code(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Δοκιμή',
            'doy_rescom' => 93759,
            'address_polh_rescom' => 8,
        ]);

        $this->actingAs($user)
            ->get(route('students.show', ['student' => $studentId, 'panel' => 'student']))
            ->assertOk()
            ->assertSee('name="doy_rescom"', false)
            ->assertSee('Α&#039; ΠΑΤΡΩΝ', false)
            ->assertSee('ΡΟΔΟΠΗΣ - Κομοτηνή');

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), ['doy_rescom' => 99999999])
            ->assertSessionHasErrors('doy_rescom');

        $this->actingAs($user)
            ->patch(route('students.section.update', [$studentId, 'student']), ['doy_rescom' => 93815])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', ['id' => $studentId, 'doy_rescom' => 93815]);
    }

    public function test_authenticated_user_can_search_rescom_cities(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('rescom.cities', ['q' => 'Κομοτηνή']))
            ->assertOk()
            ->assertJsonFragment(['id' => '8', 'text' => 'ΡΟΔΟΠΗΣ - Κομοτηνή']);
    }
}
