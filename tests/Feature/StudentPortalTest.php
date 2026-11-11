<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentApografiko;
use App\Models\StudentQuestionnaire;
use App\Models\User;
use App\Support\RescomReferenceData;
use App\Support\VisibleInternshipDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_their_workflow_and_not_another_students_data(): void
    {
        $student = $this->student(['fName' => 'Μαρία', 'lName' => 'Δοκιμή']);
        $this->student(['fName' => 'Απόρρητος', 'lName' => 'Φοιτητής']);

        $this->actingAs($student, 'student')
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Δοκιμή Μαρία')
            ->assertSee('Στοιχεία πρακτικής')
            ->assertSee('Εισαγωγή στοιχείων φοιτητή')
            ->assertDontSee('Απόρρητος');
    }

    public function test_actions_are_read_only_and_records_are_available_from_the_left_menu(): void
    {
        $student = $this->student(['funding' => 'Espa']);

        $this->actingAs($student, 'student')
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Δελτία / Αξιολογήσεις')
            ->assertDontSee(route('student.questionnaire'), false)
            ->assertDontSee('Ανέβασμα αρχείου');

        $this->get(route('student.records'))
            ->assertOk()
            ->assertSee('απογραφικού δελτίου εισόδου')
            ->assertSee('αναλυτικής έκθεσης πεπραγμένων φοιτητή')
            ->assertSee(route('student.apografika.show', 'input'), false)
            ->assertSee(route('student.questionnaire'), false)
            ->assertSee('Συμπλήρωση');

        StudentApografiko::create(['student_id' => $student->id, 'type' => 'input', 'status' => 'completed']);
        StudentQuestionnaire::create([
            's_id' => $student->id,
            'version' => 1,
            'answers' => [],
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $student->unsetRelation('apografika')->unsetRelation('questionnaire');

        $this->get(route('student.records'))
            ->assertOk()
            ->assertSee('href="'.route('student.apografika.pdf', 'input').'"', false)
            ->assertDontSee('href="'.route('student.apografika.show', 'input').'"', false)
            ->assertSee('href="'.route('student.questionnaire.pdf').'"', false)
            ->assertDontSee('href="'.route('student.questionnaire').'"', false);
    }

    public function test_dashboard_structure_comes_from_the_workflow_tables(): void
    {
        $student = $this->student(['funding' => 'Espa']);
        DB::table('workflow_actions')->where('funding_code', 'all')->where('code', 'stoix_student')
            ->update(['title' => 'Τίτλος από τη βάση', 'sort_order' => 2.5]);

        $this->actingAs($student, 'student')->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Τίτλος από τη βάση')
            ->assertSee('Α2')
            ->assertDontSee('A2.5');
    }

    public function test_student_can_submit_personal_details_once(): void
    {
        $admin = User::factory()->create(['id' => 1, 'role' => 'admin']);
        $student = $this->student(['funding' => 'Espa']);
        $rescomReferenceData = app(RescomReferenceData::class);

        $data = [
            'fName' => 'μαρία', 'lName' => 'δοκιμή',
            'amka' => '12345678901', 'afm' => '123456789', 'id_group' => 15,
            'ama' => '1234567', 'fylo_rescom' => 1, 'iban' => 'GR'.str_repeat('1', 25),
            'adt' => 'ΑΒ123456', 'adt_ek_arxh' => 'ΑΤ ΑΘΗΝΩΝ', 'adt_ek_date' => '2020-01-01',
            'fatFName' => 'ΓΙΩΡΓΟΣ', 'fatLName' => 'ΔΟΚΙΜΗ', 'momFName' => 'ΕΛΕΝΗ', 'momLName' => 'ΔΟΚΙΜΗ',
            'katEkp_rescom' => 3, 'oikKat' => 'ΑΓΑΜΟΣ', 'syzName' => '-', 'numChild' => 0,
            'doy_rescom' => array_key_first($rescomReferenceData->options('doys')), 'dob' => '2000-01-01',
            'placeDob' => 'ΑΘΗΝΑ', 'ithagen' => 'ΕΛΛΗΝΙΚΗ',
            'xwra_rescom' => array_key_first($rescomReferenceData->options('countries')),
            'address_polh_rescom' => array_key_first($rescomReferenceData->options('cities')),
            'address_odos' => 'ΟΔΟΣ 1', 'address_tk' => '12345', 'workDaysDescription' => 'ΔΕΥΤΕΡΑ-ΠΑΡΑΣΚΕΥΗ',
            'startDate' => '2026-01-01', 'endDate' => '2026-03-31',
            'numMhnes' => 3, 'wresPerWeek' => 40, 'payPerMonth' => '999.99',
            'payTotal' => 1,
        ];

        $this->actingAs($student, 'student')
            ->from(route('student.details.student.edit'))
            ->patch(route('student.details.student.update'), [...$data, 'iban' => ';ΣΣΣ'])
            ->assertRedirect(route('student.details.student.edit'))
            ->assertSessionHasErrors('iban');

        $this->actingAs($student, 'student')
            ->patch(route('student.details.student.update'), $data)
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id, 'fName' => 'ΜΑΡΙΑ', 'lName' => 'ΔΟΚΙΜΗ',
            'afm' => '123456789', 'wresPerWeek' => 40, 'payPerMonth' => 350, 'payTotal' => 1050,
        ]);
        $this->assertDatabaseHas('student_actions', ['student_id' => $student->id, 'action' => 'stoix_student', 'status' => 'submitted']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id]);

        $this->actingAs($student->fresh(), 'student')
            ->patch(route('student.details.student.update'), $data + ['afm' => '987654321'])
            ->assertForbidden();
        $this->assertDatabaseMissing('students', ['id' => $student->id, 'afm' => '987654321']);
    }

    public function test_student_can_save_an_incomplete_profile_draft_without_completing_the_section(): void
    {
        User::factory()->create(['id' => 1, 'role' => 'admin']);
        $student = $this->student(['funding' => 'Espa', 'fName' => null, 'lName' => null]);

        $this->actingAs($student, 'student')
            ->patch(route('student.details.student.update'), [
                'submission' => 'draft',
                'fName' => 'μαρία',
                'lName' => 'δοκιμή',
            ])
            ->assertRedirect(route('student.details.student.edit'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'fName' => 'ΜΑΡΙΑ',
            'lName' => 'ΔΟΚΙΜΗ',
        ]);
        $this->assertDatabaseHas('student_actions', ['student_id' => $student->id, 'action' => 'stoix_student', 'status' => 'draft']);
        $this->assertDatabaseCount('notifications', 0);

        $this->actingAs($student->fresh(), 'student')
            ->patch(route('student.details.student.update'), [
                'submission' => 'draft',
                'fName' => '',
            ])
            ->assertRedirect(route('student.details.student.edit'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'fName' => null,
            'lName' => 'ΔΟΚΙΜΗ',
        ]);

        $student = $student->fresh();

        $this->actingAs($student, 'student')
            ->get(route('student.details.student.edit'))
            ->assertOk()
            ->assertSee('value="'.$student->lName.'"', false);
    }

    public function test_clearing_espa_hours_in_a_draft_also_clears_the_calculated_amounts(): void
    {
        $student = $this->student([
            'funding' => 'Espa',
            'wresPerWeek' => 40,
            'numMhnes' => 3,
            'payPerMonth' => 350,
            'payTotal' => 1050,
        ]);

        $this->actingAs($student, 'student')
            ->patch(route('student.details.student.update'), [
                'submission' => 'draft',
                'wresPerWeek' => '',
                'numMhnes' => 3,
            ])
            ->assertRedirect(route('student.details.student.edit'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'wresPerWeek' => null,
            'payPerMonth' => null,
            'payTotal' => null,
        ]);
    }

    public function test_student_can_submit_provider_and_invalid_data_does_not_complete_it(): void
    {
        $admin = User::factory()->create(['id' => 1, 'role' => 'admin']);
        $student = $this->student(['funding' => 'Espa']);
        $rescomReferenceData = app(RescomReferenceData::class);

        $this->actingAs($student, 'student')
            ->from(route('student.details.provider.edit'))
            ->patch(route('student.details.provider.update'), ['f_afm' => 'abc'])
            ->assertRedirect(route('student.details.provider.edit'))
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('student_actions', ['student_id' => $student->id, 'action' => 'stoix_forea']);
        $this->assertDatabaseMissing('providers', ['s_id' => $student->id]);

        $this->actingAs($student, 'student')
            ->from(route('student.details.provider.edit'))
            ->patch(route('student.details.provider.update'), ['f_email' => 'not-an-email'])
            ->assertRedirect(route('student.details.provider.edit'))
            ->assertSessionHasErrors('f_email');

        $institutionStudent = $this->student(['funding' => 'Institution']);
        $this->actingAs($institutionStudent, 'student')
            ->from(route('student.details.provider.edit'))
            ->patch(route('student.details.provider.update'), ['f_tk' => '1234'])
            ->assertRedirect(route('student.details.provider.edit'))
            ->assertSessionHasErrors('f_tk');

        $data = [
            'f_epwnymia' => 'ΔΟΚΙΜΑΣΤΙΚΟΣ ΦΟΡΕΑΣ', 'f_nomikhMorfh_rescom' => 3,
            'f_doy_rescom' => array_key_first($rescomReferenceData->options('doys')), 'dhm_idiwt_rescom' => 2,
            'f_proeleush_rescom' => 1, 'f_afm' => '987654321',
            'f_polh_rescom' => array_key_first($rescomReferenceData->options('cities')),
            'f_xwra_rescom' => array_key_first($rescomReferenceData->options('countries')),
            'f_odos' => 'ΟΔΟΣ 2', 'f_tk' => '12345', 'f_perifereia' => 2,
            'f_thl' => '2101234567', 'f_email' => 'provider@example.test',
            'f_ekprFname' => 'ΕΚΠΡΟΣΩΠΟΣ', 'f_ekprLname' => 'ΔΟΚΙΜΗ',
            'f_ekprIdiothta' => 'ΔΙΕΥΘΥΝΤΗΣ',
            'f_ektPrakt_polh_rescom' => array_key_first($rescomReferenceData->options('cities')),
            'f_ektPrakt_odos' => 'ΟΔΟΣ ΠΡΑΚΤΙΚΗΣ 3', 'f_ektPrakt_tk' => '12345',
            'f_epopt_foreaFname' => 'ΕΠΟΠΤΗΣ',
            'f_epopt_foreaLname' => 'ΔΟΚΙΜΗ', 'f_epopt_email' => 'supervisor@example.test',
            'f_epopt_foreaThesh' => 'ΥΠΕΥΘΥΝΟΣ', 'f_epopt_foreaKatartish' => 'ΕΜΠΕΙΡΙΑ',
        ];

        $this->actingAs($student, 'student')
            ->patch(route('student.details.provider.update'), $data)
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('providers', [
            's_id' => $student->id, 'f_epwnymia' => 'ΔΟΚΙΜΑΣΤΙΚΟΣ ΦΟΡΕΑΣ', 'f_afm' => '987654321',
        ]);
        $this->assertDatabaseHas('student_actions', ['student_id' => $student->id, 'action' => 'stoix_forea', 'status' => 'submitted']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id]);
    }

    public function test_student_can_save_and_clear_an_incomplete_provider_draft_without_completing_the_section(): void
    {
        $student = $this->student(['funding' => 'Espa']);

        $this->actingAs($student, 'student')
            ->patch(route('student.details.provider.update'), [
                'submission' => 'draft',
                'f_epwnymia' => 'δοκιμαστικός φορέας',
                'f_email' => 'provider@example.test',
            ])
            ->assertRedirect(route('student.details.provider.edit'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('providers', [
            's_id' => $student->id,
            'f_epwnymia' => 'ΔΟΚΙΜΑΣΤΙΚΟΣ ΦΟΡΕΑΣ',
            'f_email' => 'provider@example.test',
        ]);
        $this->assertDatabaseHas('student_actions', ['student_id' => $student->id, 'action' => 'stoix_forea', 'status' => 'draft']);

        $this->actingAs($student->fresh(), 'student')
            ->patch(route('student.details.provider.update'), [
                'submission' => 'draft',
                'f_epwnymia' => '',
            ])
            ->assertRedirect(route('student.details.provider.edit'));

        $this->assertDatabaseHas('providers', [
            's_id' => $student->id,
            'f_epwnymia' => null,
            'f_email' => 'provider@example.test',
        ]);

        $this->actingAs($student->fresh(), 'student')
            ->get(route('student.details.provider.edit'))
            ->assertOk()
            ->assertSee('value="provider@example.test"', false)
            ->assertSee('value="draft"', false);
    }

    public function test_student_pages_require_student_authentication(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect(route('student.login'));
        $this->get(route('student.details.student.edit'))->assertRedirect(route('student.login'));
        $this->get(route('student.details.provider.edit'))->assertRedirect(route('student.login'));
    }

    public function test_personal_form_fields_follow_student2_for_each_funding(): void
    {
        $espa = $this->student(['funding' => 'Espa']);
        $this->actingAs($espa, 'student')->get(route('student.details.student.edit'))
            ->assertOk()
            ->assertSee('name="fName"', false)
            ->assertSee('name="startDate"', false)
            ->assertSee('name="payPerMonth"', false)
            ->assertSee('name="payTotal"', false)
            ->assertDontSee('name="doseis_count"', false)
            ->assertSee('name="wresPerWeek"', false)
            ->assertSee('20 - Μερική απασχόληση')
            ->assertSee('40 - Πλήρης απασχόληση')
            ->assertSee('student-compensation.js')
            ->assertSee('<option value="2.5"', false)
            ->assertSee('11 ψηφία.')
            ->assertSee('oninput="this.value = this.value.toUpperCase()"', false)
            ->assertSee('readonly', false)
            ->assertDontSee('data-bs-toggle="tooltip"', false)
            ->assertDontSee('name="wresPerDays"', false);

        $provider = $this->student(['funding' => 'InternshipProvider']);
        $this->actingAs($provider, 'student')->get(route('student.details.student.edit'))
            ->assertOk()
            ->assertSee('name="adt_ek_arxh"', false)
            ->assertSee('name="oikKat"', false)
            ->assertSee('name="wresPerDays"', false)
            ->assertSee('name="payTotal"', false)
            ->assertSee('name="payPerMonth"', false);

        $institution = $this->student(['funding' => 'Institution']);
        $this->actingAs($institution, 'student')->get(route('student.details.student.edit'))
            ->assertOk()
            ->assertSee('name="adt_ek_arxh"', false)
            ->assertSee('name="oikKat"', false)
            ->assertSee('name="wresPerWeek"', false)
            ->assertDontSee('name="wresPerDays"', false)
            ->assertDontSee('name="payTotal"', false)
            ->assertDontSee('name="payPerMonth"', false);
    }

    public function test_provider_form_fields_follow_forea2_for_each_funding(): void
    {
        foreach (['Espa', 'InternshipProvider', 'Institution'] as $funding) {
            $student = $this->student(['funding' => $funding]);
            $this->actingAs($student, 'student')->get(route('student.details.provider.edit'))
                ->assertOk()
                ->assertSee('name="f_xwra_rescom"', false)
                ->assertSee('name="f_polh_rescom"', false)
                ->assertSee('name="f_odos"', false)
                ->assertSee('name="f_tk"', false)
                ->assertSee('name="f_ektPrakt_polh_rescom"', false)
                ->assertSee('name="f_ektPrakt_odos"', false)
                ->assertSee('name="f_ektPrakt_tk"', false)
                ->assertSee('name="f_ekprIdiothta"', false)
                ->assertDontSee('name="f_perioxh"', false)
                ->assertDontSee('name="f_topos_ekt_prakt"', false);
        }

        $espa = $this->student(['funding' => 'Espa']);
        $this->actingAs($espa, 'student')->get(route('student.details.provider.edit'))
            ->assertOk()
            ->assertSee('name="f_nomikhMorfh_rescom"', false);

        $provider = $this->student(['funding' => 'InternshipProvider']);
        $this->actingAs($provider, 'student')->get(route('student.details.provider.edit'))
            ->assertOk()
            ->assertSee('name="f_antikeim_Praktikhs"', false)
            ->assertDontSee('name="f_nomikhMorfh_rescom"', false);

        $institution = $this->student(['funding' => 'Institution']);
        $this->actingAs($institution, 'student')->get(route('student.details.provider.edit'))
            ->assertOk()
            ->assertDontSee('name="f_antikeim_Praktikhs"', false);
    }

    public function test_student_can_review_only_their_funding_specific_details(): void
    {
        $city = array_key_first(app(RescomReferenceData::class)->options('cities'));
        $student = $this->student([
            'funding' => 'Espa',
            'fName' => 'ΜΑΡΙΑ',
            'address_polh_rescom' => $city,
            'payTotal' => 700,
        ]);
        $other = $this->student(['fName' => 'ΑΠΟΡΡΗΤΟΣ']);
        DB::table('providers')->insert([
            's_id' => $student->id,
            'f_epwnymia' => 'ΔΟΚΙΜΑΣΤΙΚΟΣ ΦΟΡΕΑΣ',
        ]);
        DB::table('providers')->insert(['s_id' => $other->id, 'f_epwnymia' => 'ΑΠΟΡΡΗΤΟΣ ΦΟΡΕΑΣ']);

        $this->actingAs($student, 'student')
            ->get(route('student.details'))
            ->assertOk()
            ->assertSee('Στοιχεία πρακτικής')
            ->assertSee('Γενικά στοιχεία πρακτικής')
            ->assertSee('Στοιχεία φάσης')
            ->assertSee('Στοιχεία τμήματος')
            ->assertSee('accordion-button collapsed', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertDontSee('student-internship-details-3', false)
            ->assertDontSee('ΑΠΟΡΡΗΤΟΣ');
    }

    public function test_student_details_visibility_comes_only_from_the_fields_table(): void
    {
        $student = $this->student(['notes' => 'ΣΗΜΕΙΩΣΗ ΑΠΟ ΤΗ ΒΑΣΗ']);

        DB::table('fields')
            ->where('card_section', 'student')
            ->where('field', 'notes')
            ->update([
                'label' => 'Πρόσθετο πεδίο βάσης',
                'visible_to_roles' => json_encode(['student']),
                'active' => true,
            ]);

        $studentDetails = collect(app(VisibleInternshipDetails::class)->forRole(
            $student,
            app(RescomReferenceData::class),
            'student',
        ))->firstWhere('key', 'student');

        $this->assertContains('Πρόσθετο πεδίο βάσης', array_column($studentDetails['details'], 'label'));
        $this->assertContains('ΣΗΜΕΙΩΣΗ ΑΠΟ ΤΗ ΒΑΣΗ', array_column($studentDetails['details'], 'value'));
    }

    public function test_imported_student_fields_are_visible_disabled_and_cannot_be_updated(): void
    {
        $student = $this->student([
            'AMF' => '1234567890123',
            'tel' => '6912345678',
            'email' => 'student@example.test',
            'epopt_kath' => 'Μαρία Καθηγήτρια',
        ]);

        $this->actingAs($student, 'student')
            ->get(route('student.details.student.edit'))
            ->assertOk()
            ->assertSee('id="readonly-AMF"', false)
            ->assertSee('id="readonly-tel"', false)
            ->assertSee('id="readonly-email"', false)
            ->assertSee('id="readonly-epopt_kath"', false)
            ->assertSee('disabled', false);

        $this->actingAs($student, 'student')
            ->patch(route('student.details.student.update'), [
                'submission' => 'draft',
                'AMF' => '9999999999999',
                'tel' => '6999999999',
                'email' => 'changed@example.test',
                'epopt_kath' => 'Άλλος Καθηγητής',
            ])
            ->assertRedirect(route('student.details.student.edit'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'AMF' => '1234567890123',
            'tel' => '6912345678',
            'email' => 'student@example.test',
            'epopt_kath' => 'Μαρία Καθηγήτρια',
        ]);
    }

    public function test_student_can_submit_questionnaire_and_then_view_only_the_pdf(): void
    {
        $student = $this->eligibleQuestionnaireStudent();
        $questions = DB::table('student_questionnaire_questions')->where('version', 2)->get();
        $answers = $questions->mapWithKeys(fn ($question) => [
            $question->code => $question->type === 'radio'
                ? json_decode($question->options, true)[0]
                : 'Δοκιμαστική αναλυτική έκθεση',
        ])->all();

        $this->actingAs($student, 'student')
            ->get(route('student.questionnaire'))
            ->assertOk()
            ->assertSee('Έκδοση 2')
            ->assertSee('Οριστική υποβολή');

        $this->actingAs($student, 'student')
            ->post(route('student.questionnaire.store'), ['answers' => $answers])
            ->assertRedirect(route('student.questionnaire'));

        $questionnaire = StudentQuestionnaire::query()->whereBelongsTo($student)->first();

        $this->assertNotNull($questionnaire);
        $this->assertCount($questions->count(), $questionnaire->answers);
        $this->assertSame(StudentQuestionnaire::STATUS_SUBMITTED, $questionnaire->status);

        $this->actingAs($student->fresh(), 'student')
            ->get(route('student.questionnaire'))
            ->assertOk()
            ->assertSee('Προβολή PDF')
            ->assertDontSee('Δοκιμαστική αναλυτική έκθεση')
            ->assertDontSee('Οριστική υποβολή');

        $this->actingAs($student->fresh(), 'student')
            ->get(route('student.questionnaire.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($student->fresh(), 'student')
            ->post(route('student.questionnaire.store'), ['answers' => $answers])
            ->assertForbidden();

        $questionnaire->update(['status' => StudentQuestionnaire::STATUS_REJECTED]);

        $this->actingAs($student->fresh(), 'student')
            ->get(route('student.questionnaire.pdf'))
            ->assertRedirect(route('student.questionnaire'))
            ->assertSessionHas('error', 'Το ερωτηματολόγιο δεν έχει υποβληθεί οριστικά και το PDF δεν είναι διαθέσιμο.');

        $this->actingAs($student->fresh(), 'student')
            ->get(route('student.questionnaire'))
            ->assertOk()
            ->assertSee('Δοκιμαστική αναλυτική έκθεση')
            ->assertSee('Οριστική υποβολή');

        $updatedAnswers = $answers;
        $textQuestion = $questions->firstWhere('type', 'textarea');
        $updatedAnswers[$textQuestion->code] = 'Διορθωμένη αναλυτική έκθεση';

        $this->actingAs($student->fresh(), 'student')
            ->post(route('student.questionnaire.store'), ['answers' => $updatedAnswers])
            ->assertRedirect(route('student.questionnaire'));

        $this->assertSame(
            'Διορθωμένη αναλυτική έκθεση',
            $questionnaire->fresh()->answers[$textQuestion->code],
        );
    }

    public function test_student_report_is_required_for_final_questionnaire_submission(): void
    {
        $student = $this->eligibleQuestionnaireStudent();

        $this->actingAs($student, 'student')
            ->post(route('student.questionnaire.store'), ['submission' => 'final', 'answers' => []])
            ->assertSessionHasErrors('answers.comm');
    }

    public function test_student_can_save_and_clear_an_incomplete_questionnaire_draft(): void
    {
        $student = $this->eligibleQuestionnaireStudent();

        $this->actingAs($student, 'student')
            ->post(route('student.questionnaire.store'), [
                'submission' => 'draft',
                'answers' => ['comm' => 'Πρόχειρο κείμενο'],
            ])
            ->assertRedirect(route('student.questionnaire'));

        $questionnaire = StudentQuestionnaire::query()->whereBelongsTo($student)->firstOrFail();
        $this->assertNull($questionnaire->submitted_at);
        $this->assertSame('Πρόχειρο κείμενο', $questionnaire->answers['comm']);
        $this->assertSame(StudentQuestionnaire::STATUS_DRAFT, $questionnaire->status);

        $this->actingAs($student->fresh(), 'student')
            ->get(route('student.questionnaire'))
            ->assertOk()
            ->assertSee('Πρόχειρο κείμενο');

        $this->actingAs($student->fresh(), 'student')
            ->post(route('student.questionnaire.store'), [
                'submission' => 'draft',
                'answers' => [],
            ])
            ->assertRedirect(route('student.questionnaire'));

        $this->assertNull($questionnaire->fresh()->answers['comm']);
    }

    public function test_questionnaire_is_not_available_before_workflow_requirements(): void
    {
        $student = $this->student([
            'funding' => 'Espa',
            'startDate' => '2026-01-01',
            'endDate' => '2026-02-01',
        ]);

        $this->actingAs($student, 'student')
            ->get(route('student.questionnaire'))
            ->assertForbidden();

        $this->actingAs($student, 'student')
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Πρέπει πρώτα να ολοκληρωθεί η ενότητα Β.');
    }

    public function test_questionnaire_uses_the_version_assigned_to_the_student(): void
    {
        $student = $this->eligibleQuestionnaireStudent();
        $student->forceFill(['questionnaire_version' => 1])->save();

        $this->actingAs($student->fresh(), 'student')
            ->get(route('student.questionnaire'))
            ->assertOk()
            ->assertSee('Έκδοση 1')
            ->assertSee('Γενικές ερωτήσεις για την πρακτική άσκηση')
            ->assertDontSee('Οργάνωση και Διαδικασία Πρακτικής Άσκησης');
    }

    private function eligibleQuestionnaireStudent(): Student
    {
        $student = $this->student([
            'funding' => 'Espa',
            'startDate' => '2026-01-01',
            'endDate' => '2026-02-01',
        ]);

        $this->approveStudentDetails($student->id);
        DB::table('student_actions')->insert([
            ['student_id' => $student->id, 'action' => 'section_a_approval', 'status' => 'approved'],
            ['student_id' => $student->id, 'action' => 'section_b_approval', 'status' => 'approved'],
        ]);

        StudentApografiko::create([
            'student_id' => $student->id,
            'type' => 'input',
            'status' => StudentApografiko::STATUS_COMPLETED,
        ]);

        foreach (['symf_initial', 'symf_SN_f_f', 'symf_SN_FULL'] as $code) {
            DB::table('student_documents')->insert([
                's_id' => $student->id,
                'document_type_id' => DB::table('document_types')->where('code', $code)->value('id'),
                'version' => 1,
                'disk' => 'local',
                'path' => "students/{$student->id}/documents/{$code}.pdf",
                'original_name' => "{$code}.pdf",
                'mime_type' => 'application/pdf',
                'size' => 100,
                'status' => 'approved',
                'uploaded_by_role' => 'member',
                'uploaded_by_id' => 1,
                'uploaded_at' => now(),
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->completeWorkflowSection($student, 'A');
        $this->completeWorkflowSection($student, 'B');

        return $student->fresh();
    }

    private function student(array $attributes = []): Student
    {
        $fundingCode = $attributes['funding'] ?? 'Espa';
        unset($attributes['funding']);

        $departmentId = DB::table('departments')->value('id')
            ?? DB::table('departments')->insertGetId(['atlasId' => 999, 'tmhma' => 'ΔΟΚΙΜΑΣΤΙΚΟ']);
        $fundingId = DB::table('fundings')->where('code', $fundingCode)->value('id');
        $praktikiId = DB::table('praktiki_cycles')
            ->where('funding_id', $fundingId)
            ->where('praxh_year', '2025-2026')
            ->value('id')
            ?? DB::table('praktiki_cycles')->insertGetId([
                'funding_id' => $fundingId,
                'praxh_year' => '2025-2026',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        $fashId = DB::table('praktiki_phases')
            ->where('department_id', $departmentId)
            ->where('praktiki_cycle_id', $praktikiId)
            ->value('id')
            ?? DB::table('praktiki_phases')->insertGetId([
                'legacy_fid' => DB::table('praktiki_phases')->max('legacy_fid') + 1,
                'department_id' => $departmentId,
                'praktiki_cycle_id' => $praktikiId,
                'name' => 'Α',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $id = DB::table('students')->insertGetId($attributes + [
            'department_id' => $departmentId,
            'fash_id' => $fashId,
            'fName' => 'Δοκιμή',
            'lName' => 'Φοιτητής',
        ]);

        return Student::findOrFail($id);
    }
}
