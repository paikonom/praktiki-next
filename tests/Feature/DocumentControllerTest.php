<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentApografiko;
use App\Models\StudentDocument;
use App\Models\StudentQuestionnaire;
use App\Models\User;
use App\Models\WorkflowAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_uploads_multiple_versions_without_replacing_history(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $this->completeBeforeStart($student);
        $type = DocumentType::where('code', 'ekthesh_Foit')->firstOrFail();

        $this->actingAs($student, 'student')
            ->post(route('student.documents.store', $type), ['file' => UploadedFile::fake()->create('first.pdf', 100, 'application/pdf')])
            ->assertRedirect();
        StudentDocument::where('document_type_id', $type->id)->firstOrFail()
            ->update(['status' => 'rejected', 'reviewed_at' => now()]);
        $this->actingAs($student, 'student')
            ->post(route('student.documents.store', $type), ['file' => UploadedFile::fake()->create('second.pdf', 100, 'application/pdf')])
            ->assertRedirect();

        $documents = StudentDocument::where('s_id', $student->id)
            ->where('document_type_id', $type->id)
            ->orderBy('version')
            ->get();
        $this->assertSame([1, 2], $documents->pluck('version')->all());
        $this->assertSame(['first.pdf', 'second.pdf'], $documents->pluck('original_name')->all());
        Storage::disk('local')->assertExists($documents[0]->path);
        Storage::disk('local')->assertExists($documents[1]->path);
    }

    public function test_new_version_is_blocked_while_waiting_or_after_approval(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $this->completeBeforeStart($student);
        $type = DocumentType::where('code', 'ekthesh_Foit')->firstOrFail();
        $document = $this->document($student, $type);

        $this->actingAs($student, 'student')->post(route('student.documents.store', $type), [
            'file' => UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf'),
        ])->assertForbidden();

        $document->update(['status' => 'approved', 'reviewed_at' => now()]);
        $this->actingAs($student, 'student')->post(route('student.documents.store', $type), [
            'file' => UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
        $this->assertSame(1, StudentDocument::where('document_type_id', $type->id)->count());
    }

    public function test_each_portal_sees_only_document_types_allowed_for_its_role(): void
    {
        $student = $this->student();
        DocumentType::where('code', 'ekthesh_Foit')->update([
            'instructions' => "Κατεβάστε το <a href='https://example.test/form.pdf'>έντυπο</a>.",
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('students.show', ['student' => $student, 'panel' => 'documents']))
            ->assertOk()
            ->assertSee('href="https://example.test/form.pdf"', false)
            ->assertSee('Αναλυτική έκθεση πεπραγμένων φοιτητή')
            ->assertSee('Έκθεση επίδοσης από επόπτη φορέα')
            ->assertSee('Προεπισκόπηση βεβαίωσης φορέα')
            ->assertSee('Προεπισκόπηση βεβαίωσης Υπεύθυνου/η Π.Τ.');

        $this->actingAs($admin)
            ->get(route('students.completion-certificates.provider', $student))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get(route('students.completion-certificates.supervisor', $student))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($student, 'provider')->get(route('provider.documents'))
            ->assertOk()
            ->assertSee('Έκθεση επίδοσης από επόπτη φορέα')
            ->assertDontSee('Αναλυτική έκθεση πεπραγμένων φοιτητή');
    }

    public function test_handler_sees_only_document_types_for_the_students_funding(): void
    {
        $student = $this->student();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('students.show', ['student' => $student, 'panel' => 'documents']))
            ->assertOk()
            ->assertSee('Αναλυτική έκθεση πεπραγμένων φοιτητή')
            ->assertDontSee('Βεβαίωση ενσήμων e-ΕΦΚΑ από φοιτητή');
    }

    public function test_documents_follow_the_workflow_section_and_action_order(): void
    {
        $student = $this->student();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('students.show', ['student' => $student, 'panel' => 'documents']))
            ->assertOk()
            ->assertSeeInOrder([
                'Πριν την έναρξη',
                'Έντυπο έναρξης ΕΡΓΑΝΗ Ε3.5',
                'Κατά τη διάρκεια',
                'Απογραφικό δελτίο εισόδου',
                'Μετά τη λήξη',
                'Απογραφικό δελτίο εξόδου',
                'Έκθεση επίδοσης από επόπτη φορέα',
                'Βεβαίωση ολοκλήρωσης φορέα',
            ]);
    }

    public function test_document_required_after_internship_is_locked_when_end_date_is_missing(): void
    {
        $student = $this->student();
        DB::table('students')->where('id', $student->id)->update(['endDate' => null]);
        $student = $student->fresh();
        $type = DocumentType::where('code', 'ekthesh_Foit')->firstOrFail();

        $this->assertSame('missing_end_date', $type->availabilityIssueFor($student, 'member'));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('students.show', ['student' => $student, 'panel' => 'documents']))
            ->assertOk()
            ->assertSee('Δεν έχει οριστεί ημερομηνία λήξης της πρακτικής.');

        $this->actingAs($student, 'student')->post(route('student.documents.store', $type), [
            'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_student_can_delete_own_unreviewed_upload_but_not_a_reviewed_one(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $this->completeBeforeStart($student);
        $type = DocumentType::where('code', 'ekthesh_Foit')->firstOrFail();
        $this->actingAs($student, 'student')->post(route('student.documents.store', $type), [
            'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        ]);
        $document = StudentDocument::where('document_type_id', $type->id)->firstOrFail();

        $this->actingAs($student, 'student')->delete(route('student.documents.destroy', $document))->assertRedirect();
        $this->assertDatabaseMissing('student_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);

        $this->actingAs($student, 'student')->post(route('student.documents.store', $type), [
            'file' => UploadedFile::fake()->create('checked.pdf', 100, 'application/pdf'),
        ]);
        $checked = StudentDocument::where('document_type_id', $type->id)->firstOrFail();
        $checked->update(['status' => 'approved', 'reviewed_at' => now()]);

        $this->actingAs($student, 'student')->delete(route('student.documents.destroy', $checked))->assertForbidden();
        $this->assertDatabaseHas('student_documents', ['id' => $checked->id]);
    }

    public function test_student_cannot_upload_a_provider_document_or_download_another_students_document(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $other = $this->student();
        $providerType = DocumentType::where('code', 'vevaiwsh_Forea')->firstOrFail();

        $this->actingAs($student, 'student')->post(route('student.documents.store', $providerType), [
            'file' => UploadedFile::fake()->create('provider.pdf', 100, 'application/pdf'),
        ])->assertForbidden();

        $document = $this->document($other, DocumentType::where('code', 'ekthesh_Foit')->firstOrFail());
        $this->actingAs($student, 'student')->get(route('student.documents.download', $document))->assertForbidden();
        $this->actingAs($student, 'student')->get(route('student.documents.preview', $document))->assertForbidden();
    }

    public function test_student_can_preview_pdf_inline_or_download_it_as_an_attachment(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $document = $this->document($student, DocumentType::where('code', 'ekthesh_Foit')->firstOrFail());

        $this->actingAs($student, 'student')
            ->get(route('student.documents.preview', $document))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename=test.pdf');

        $this->actingAs($student, 'student')
            ->get(route('student.documents.download', $document))
            ->assertOk()
            ->assertDownload('test.pdf');
    }

    public function test_provider_can_upload_only_provider_documents_for_the_authenticated_student(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $this->completeBeforeStart($student, true);
        $providerType = DocumentType::where('code', 'vevaiwsh_Forea')->firstOrFail();
        $studentType = DocumentType::where('code', 'ekthesh_Foit')->firstOrFail();

        $this->actingAs($student, 'provider')->post(route('provider.documents.store', $providerType), [
            'file' => UploadedFile::fake()->create('provider.pdf', 100, 'application/pdf'),
        ])->assertRedirect();
        $this->assertDatabaseHas('student_documents', [
            's_id' => $student->id, 'document_type_id' => $providerType->id, 'uploaded_by_role' => 'provider',
        ]);

        $this->actingAs($student, 'provider')->post(route('provider.documents.store', $studentType), [
            'file' => UploadedFile::fake()->create('student.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_handler_can_review_and_admin_can_permanently_delete_a_reviewed_document(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $document = $this->document($student, DocumentType::where('code', 'ekthesh_Foit')->firstOrFail());
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch(route('students.documents.update', $document), ['status' => 'approved'])
            ->assertRedirect();
        $this->assertDatabaseHas('student_documents', ['id' => $document->id, 'status' => 'approved', 'reviewed_by' => $admin->id]);

        $this->actingAs($admin)->delete(route('students.documents.destroy', $document))->assertRedirect();
        $this->assertDatabaseMissing('student_documents', ['id' => $document->id]);
    }

    public function test_handler_must_reject_an_approved_version_before_uploading_a_replacement(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $type = DocumentType::where('code', 'symf_initial')->firstOrFail();
        $this->approveStudentDetails($student->id);
        $document = $this->document($student, $type);
        $document->update(['status' => 'approved', 'reviewed_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin']);

        $upload = ['file' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf')];

        $this->actingAs($admin)
            ->post(route('students.documents.store', [$student, $type]), $upload)
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('students.documents.update', $document), [
                'status' => 'rejected',
                'review_notes' => 'Αντικατάσταση με διορθωμένη έκδοση.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)->post(route('students.documents.store', [$student, $type]), [
            'file' => UploadedFile::fake()->create('older-version.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertDatabaseHas('student_documents', [
            's_id' => $student->id,
            'document_type_id' => $type->id,
            'version' => 2,
            'status' => 'approved',
            'uploaded_by_role' => 'member',
        ]);
    }

    public function test_handler_can_mark_a_document_not_required_and_require_it_again(): void
    {
        Storage::fake('local');
        $student = $this->student();
        $type = DocumentType::where('code', 'ekthesh_Foit')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);
        StudentQuestionnaire::create([
            's_id' => $student->id,
            'version' => 1,
            'status' => StudentQuestionnaire::STATUS_SUBMITTED,
            'answers' => [],
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('students.documents.not-required', [$student, $type]), [
            'review_notes' => 'Το έγγραφο παραδόθηκε εκτός συστήματος.',
        ])->assertRedirect();

        $notRequired = StudentDocument::firstOrFail();
        $this->assertSame('not_required', $notRequired->status);
        $this->assertNull($notRequired->path);
        $this->assertTrue($student->fresh()->hasCompletedDocument('ekthesh_Foit'));

        $this->actingAs($admin)
            ->get(route('students.show', ['student' => $student, 'panel' => 'documents']))
            ->assertOk()
            ->assertSee('Το έγγραφο παραδόθηκε εκτός συστήματος.')
            ->assertSee('Να απαιτείται ξανά')
            ->assertDontSee('action="'.route('students.documents.store', [$student, $type]).'"', false);

        $this->actingAs($admin)->post(route('students.documents.store', [$student, $type]), [
            'file' => UploadedFile::fake()->create('later.pdf', 100, 'application/pdf'),
        ])->assertForbidden();

        $this->actingAs($admin)
            ->post(route('students.documents.require-again', [$student, $type]))
            ->assertRedirect();

        $this->assertDatabaseMissing('student_documents', ['id' => $notRequired->id]);

        $this->actingAs($admin)->post(route('students.documents.store', [$student, $type]), [
            'file' => UploadedFile::fake()->create('later.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertTrue($student->fresh()->hasCompletedDocument('ekthesh_Foit'));
        $this->assertSame('approved', $student->fresh()->latestDocumentStatus('ekthesh_Foit'));
    }

    public function test_document_availability_uses_the_workflow_prerequisites_from_the_database(): void
    {
        $student = $this->student();
        $type = DocumentType::where('code', 'symf_SN_f_f')->firstOrFail();

        $this->assertSame('previous_step', $type->availabilityIssueFor($student));

        DB::table('workflow_actions')
            ->where('source_type', 'document')
            ->where('source_key', 'symf_SN_f_f')
            ->update(['prerequisites' => json_encode([])]);

        $type->unsetRelation('workflowActions');

        $this->assertNull($type->availabilityIssueFor($student));
    }

    public function test_required_end_documents_come_from_role_and_funding_tables(): void
    {
        $student = $this->student();
        $type = DocumentType::factory()->create([
            'code' => 'extra_provider_end_document',
            'active' => true,
        ]);
        $type->fundings()->create(['funding_code' => $student->fundingCode()]);
        $type->roles()->create([
            'role' => 'provider',
            'can_view' => true,
            'can_upload' => true,
        ]);
        WorkflowAction::create([
            'funding_code' => $student->fundingCode(),
            'section' => 'G',
            'sort_order' => 90,
            'code' => 'extra_provider_end_document',
            'title' => 'Extra provider end document',
            'responsible_role' => 'provider',
            'prerequisites' => [],
            'source_type' => 'document',
            'source_key' => $type->code,
            'allowed_statuses' => ['submitted', 'approved', 'rejected', 'not_required'],
            'completed_statuses' => ['approved', 'not_required'],
            'counts_for_section' => true,
            'active' => true,
        ]);

        $this->assertContains(
            'extra_provider_end_document',
            DocumentType::requiredEndDocumentCodes($student, 'provider')->all(),
        );
        $this->assertNotContains(
            'extra_provider_end_document',
            DocumentType::requiredEndDocumentCodes($student, 'student')->all(),
        );
    }

    private function student(): Student
    {
        $id = DB::table('students')->insertGetId([
            'fName' => 'ΔΟΚΙΜΗ', 'lName' => 'ΦΟΙΤΗΤΗΣ',
            'fash_id' => $this->internshipPhase(),
            'startDate' => now()->subMonth(),
            'endDate' => now()->subDay(),
        ]);

        return Student::findOrFail($id);
    }

    private function document(Student $student, DocumentType $type): StudentDocument
    {
        Storage::disk('local')->put("students/{$student->id}/documents/test.pdf", '%PDF-test');

        return StudentDocument::create([
            's_id' => $student->id, 'document_type_id' => $type->id, 'version' => 1,
            'disk' => 'local', 'path' => "students/{$student->id}/documents/test.pdf",
            'original_name' => 'test.pdf', 'mime_type' => 'application/pdf', 'size' => 9,
            'status' => 'submitted', 'uploaded_by_role' => 'student',
            'uploaded_by_id' => $student->id, 'uploaded_at' => now(),
        ]);
    }

    private function completeBeforeStart(Student $student, bool $provider = false): void
    {
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

        StudentQuestionnaire::updateOrCreate(
            ['s_id' => $student->id],
            [
                'version' => 1,
                'status' => StudentQuestionnaire::STATUS_SUBMITTED,
                'answers' => [],
                'submitted_at' => now(),
            ],
        );

        $codes = ['symf_initial', 'symf_SN_f_f', 'symf_SN_FULL'];
        if ($provider) {
            $codes = [...$codes, 'ADA_diaugeia', 'erganh_enarxh'];
        }

        foreach ($codes as $code) {
            $this->document($student, DocumentType::where('code', $code)->firstOrFail())
                ->update(['status' => 'approved', 'reviewed_at' => now()]);
        }

        $this->completeWorkflowSection($student, 'A');
        $this->completeWorkflowSection($student, 'B');
    }
}
