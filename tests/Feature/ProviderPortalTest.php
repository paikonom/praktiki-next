<?php

namespace Tests\Feature;

use App\Models\ProviderQuestionnaire;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProviderPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_logs_in_with_the_students_unique_access_code(): void
    {
        $student = $this->eligibleStudent('secret-code');
        $this->assertSame('ΙΔΙΩΤΙΚΟΣ', $student->provider->organizationTypeLabel());

        $this->post(route('provider.login'), ['access_code' => 'secret-code'])
            ->assertRedirect(route('provider.dashboard'));

        $this->assertAuthenticatedAs($student, 'provider');
        $this->get(route('provider.dashboard'))
            ->assertOk()
            ->assertSee($student->full_name)
            ->assertSee('ΔΟΚΙΜΑΣΤΙΚΟΣ ΦΟΡΕΑΣ')
            ->assertSee(route('provider.documents'), false);

        $this->get(route('provider.details'))
            ->assertOk()
            ->assertSee('Γενικά στοιχεία πρακτικής')
            ->assertSee('Στοιχεία φάσης')
            ->assertSee('Στοιχεία τμήματος')
            ->assertDontSee('IBAN')
            ->assertDontSee('ΑΜΚΑ')
            ->assertDontSee('secret-code');

        $this->get(route('provider.details.section', 'student'))
            ->assertOk()
            ->assertSee('Στοιχεία φοιτητή')
            ->assertSee($student->full_name)
            ->assertDontSee('IBAN')
            ->assertDontSee('ΑΜΚΑ');

        $this->get(route('provider.details.section', 'provider'))
            ->assertOk()
            ->assertSee('Στοιχεία φορέα')
            ->assertSee('ΔΟΚΙΜΑΣΤΙΚΟΣ ΦΟΡΕΑΣ')
            ->assertDontSee('secret-code');

        $this->get(route('provider.documents'))
            ->assertOk()
            ->assertSee('Προεπισκόπηση έτοιμης βεβαίωσης PDF');

        $this->get(route('provider.completion-certificate.preview'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_provider_rejects_an_invalid_access_code(): void
    {
        $this->eligibleStudent('correct-code');

        $this->post(route('provider.login'), ['access_code' => 'wrong-code'])
            ->assertSessionHasErrors('access_code');

        $this->assertGuest('provider');
    }

    public function test_provider_actions_are_read_only_and_navigation_stays_in_the_left_menu(): void
    {
        $student = $this->eligibleStudent();

        $this->actingAs($student, 'provider')
            ->get(route('provider.dashboard'))
            ->assertOk()
            ->assertSee('Ερωτηματολόγιο')
            ->assertDontSee('>Συμπλήρωση<', false)
            ->assertDontSee('>Ανέβασμα αρχείου<', false);
    }

    public function test_provider_login_repairs_a_missing_hash_after_a_direct_code_change(): void
    {
        $student = $this->eligibleStudent('new-code');
        $student->forceFill(['provider_access_token_hash' => null])->save();

        $this->post(route('provider.login'), ['access_code' => 'new-code'])
            ->assertRedirect(route('provider.dashboard'));

        $this->assertAuthenticatedAs($student, 'provider');
        $this->assertSame(hash('sha256', 'new-code'), $student->fresh()->provider_access_token_hash);
    }

    public function test_provider_login_rejects_a_stale_hash_after_a_direct_code_change(): void
    {
        $student = $this->eligibleStudent('old-code');
        $student->forceFill(['kwdForea' => 'new-code'])->save();

        $this->post(route('provider.login'), ['access_code' => 'old-code'])
            ->assertSessionHasErrors('access_code');

        $this->assertGuest('provider');
    }

    public function test_provider_can_submit_questionnaire_and_then_view_only_the_pdf(): void
    {
        $student = $this->eligibleStudent();
        $questions = DB::table('provider_questionnaire_questions')
            ->where('version', 1)
            ->whereNotIn('code', ['G1', 'G2', 'G3', 'D1', 'D2', 'D3', 'D4', 'D5', 'D6', 'D7'])
            ->whereNotLike('code', '%_oik')
            ->get();
        $answers = $questions->mapWithKeys(fn ($question) => [
            $question->code => $question->type === 'radio'
                ? json_decode($question->options, true)[0]
                : 'Δοκιμαστική αξιολόγηση του φορέα.',
        ])->all();

        $this->actingAs($student, 'provider')
            ->post(route('provider.questionnaire.store'), ['answers' => $answers])
            ->assertRedirect(route('provider.questionnaire'));

        $questionnaire = ProviderQuestionnaire::query()->whereBelongsTo($student)->first();
        $this->assertNotNull($questionnaire);
        $this->assertCount($questions->count(), $questionnaire->answers);
        $this->assertSame(ProviderQuestionnaire::STATUS_SUBMITTED, $questionnaire->status);

        $this->actingAs($student->fresh(), 'provider')
            ->get(route('provider.questionnaire'))
            ->assertOk()
            ->assertSee('Προβολή PDF')
            ->assertDontSee('Δοκιμαστική αξιολόγηση του φορέα.')
            ->assertDontSee('Οριστική υποβολή');

        $this->actingAs($student->fresh(), 'provider')
            ->get(route('provider.questionnaire.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($student->fresh(), 'provider')
            ->post(route('provider.questionnaire.store'), ['answers' => $answers])
            ->assertForbidden();

        $questionnaire->update(['status' => ProviderQuestionnaire::STATUS_REJECTED]);

        $this->actingAs($student->fresh(), 'provider')
            ->get(route('provider.questionnaire.pdf'))
            ->assertRedirect(route('provider.questionnaire'))
            ->assertSessionHas('error', 'Το ερωτηματολόγιο δεν έχει υποβληθεί οριστικά και το PDF δεν είναι διαθέσιμο.');
    }

    public function test_provider_report_is_required_for_final_questionnaire_submission(): void
    {
        $student = $this->eligibleStudent();

        $this->actingAs($student, 'provider')
            ->post(route('provider.questionnaire.store'), ['submission' => 'final', 'answers' => []])
            ->assertSessionHasErrors('answers.comm');
    }

    public function test_provider_can_save_an_incomplete_questionnaire_draft(): void
    {
        $student = $this->eligibleStudent();

        $this->actingAs($student, 'provider')
            ->post(route('provider.questionnaire.store'), [
                'submission' => 'draft',
                'answers' => ['comm' => 'Πρόχειρη αξιολόγηση'],
            ])
            ->assertRedirect(route('provider.questionnaire'));

        $questionnaire = ProviderQuestionnaire::query()->whereBelongsTo($student)->firstOrFail();
        $this->assertNull($questionnaire->submitted_at);
        $this->assertSame('Πρόχειρη αξιολόγηση', $questionnaire->answers['comm']);
        $this->assertSame(ProviderQuestionnaire::STATUS_DRAFT, $questionnaire->status);

        $this->actingAs($student->fresh(), 'provider')
            ->get(route('provider.questionnaire'))
            ->assertOk()
            ->assertSee('Πρόχειρη αξιολόγηση');
    }

    public function test_questionnaire_includes_the_department_specific_questions(): void
    {
        $psychologyStudent = $this->eligibleStudent(departmentAtlasId: 171);

        $this->actingAs($psychologyStudent, 'provider')
            ->get(route('provider.questionnaire'))
            ->assertOk()
            ->assertSee('Γνώσεις ψυχολογίας')
            ->assertSee('Προσωπικές και διαπροσωπικές δεξιότητες');

        $economicsStudent = $this->eligibleStudent(departmentAtlasId: 309);

        $this->actingAs($economicsStudent, 'provider')
            ->get(route('provider.questionnaire'))
            ->assertOk()
            ->assertSee('Ερωτήσεις Τμήματος Οικονομικών');
    }

    public function test_questionnaire_is_not_available_before_the_internship_ends(): void
    {
        $student = $this->eligibleStudent(endDate: now()->addMonth()->toDateString());

        $this->actingAs($student, 'provider')
            ->get(route('provider.questionnaire'))
            ->assertRedirect(route('provider.dashboard'))
            ->assertSessionHas('error', 'Το ερωτηματολόγιο ανοίγει μετά τη λήξη της πρακτικής.');

        $this->actingAs($student, 'provider')
            ->get(route('provider.dashboard'))
            ->assertOk()
            ->assertSee('Το ερωτηματολόγιο ανοίγει μετά τη λήξη της πρακτικής.');
    }

    public function test_provider_cannot_use_end_actions_before_section_a_is_complete(): void
    {
        $student = $this->eligibleStudent();
        DB::table('student_actions')->where('student_id', $student->id)->update(['status' => 'submitted']);
        $student = $student->fresh();

        $this->actingAs($student, 'provider')
            ->get(route('provider.dashboard'))
            ->assertOk()
            ->assertSee('Πρέπει πρώτα να ολοκληρωθεί η ενότητα Β.')
            ->assertDontSee('>Συμπλήρωση έκθεσης<', false);

        $this->actingAs($student, 'provider')
            ->get(route('provider.questionnaire'))
            ->assertRedirect(route('provider.dashboard'))
            ->assertSessionHas('error', 'Πρέπει πρώτα να ολοκληρωθεί η ενότητα Α.');

        $typeId = DB::table('document_types')->where('code', 'ekthesh_Forea')->value('id');
        $this->actingAs($student, 'provider')
            ->post(route('provider.documents.store', $typeId), [
                'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_provider_pages_require_provider_authentication(): void
    {
        $this->get(route('provider.dashboard'))->assertRedirect(route('provider.login'));
        $this->get(route('provider.details'))->assertRedirect(route('provider.login'));
        $this->get(route('provider.questionnaire'))->assertRedirect(route('provider.login'));
        $this->get(route('provider.questionnaire.pdf'))->assertRedirect(route('provider.login'));
    }

    private function eligibleStudent(
        ?string $accessCode = null,
        int $departmentAtlasId = 100,
        ?string $endDate = null,
    ): Student {
        $accessCode ??= 'provider-code-'.$departmentAtlasId;
        $departmentId = DB::table('departments')->insertGetId([
            'atlasId' => $departmentAtlasId,
            'tmhma' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΤΜΗΜΑ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $studentId = DB::table('students')->insertGetId([
            'department_id' => $departmentId,
            'fName' => 'ΜΑΡΙΑ',
            'lName' => 'ΔΟΚΙΜΗ',
            'fash_id' => $this->internshipPhase('Espa', '2025-2026', $departmentId),
            'startDate' => now()->subMonth()->toDateString(),
            'endDate' => $endDate ?? now()->subDay()->toDateString(),
            'kwdForea' => $accessCode,
            'provider_access_token_hash' => hash('sha256', $accessCode),
        ]);
        $this->approveStudentDetails($studentId);
        DB::table('student_actions')->insert([
            ['student_id' => $studentId, 'action' => 'section_a_approval', 'status' => 'approved'],
            ['student_id' => $studentId, 'action' => 'section_b_approval', 'status' => 'approved'],
        ]);
        DB::table('providers')->insert([
            's_id' => $studentId,
            'f_epwnymia' => 'ΔΟΚΙΜΑΣΤΙΚΟΣ ΦΟΡΕΑΣ',
            'dhm_idiwt_rescom' => 2,
        ]);

        foreach (['symf_initial', 'symf_SN_f_f', 'symf_SN_FULL', 'ADA_diaugeia', 'erganh_enarxh'] as $code) {
            DB::table('student_documents')->insert([
                's_id' => $studentId,
                'document_type_id' => DB::table('document_types')->where('code', $code)->value('id'),
                'version' => 1,
                'disk' => 'local',
                'path' => "test/{$code}.pdf",
                'original_name' => "{$code}.pdf",
                'mime_type' => 'application/pdf',
                'size' => 100,
                'status' => 'approved',
                'uploaded_by_role' => 'member',
                'uploaded_by_id' => 1,
                'uploaded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $student = Student::findOrFail($studentId);
        $this->completeWorkflowSection($student, 'A');
        $this->completeWorkflowSection($student, 'B');

        return $student->fresh();
    }
}
