<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\ScientificSupervisor;
use App\Models\Student;
use App\Models\WorkflowSectionRule;
use App\Support\WorkflowDisplay;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScientificSupervisorPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_supervisor_logs_in_and_sees_only_assigned_students(): void
    {
        $departmentId = DB::table('departments')->insertGetId([
            'atlasId' => 991,
            'tmhma' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΤΜΗΜΑ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $supervisor = ScientificSupervisor::query()->forceCreate([
            'department_id' => $departmentId,
            'fName' => 'ΜΑΡΙΑ',
            'lName' => 'ΥΠΕΥΘΥΝΗ',
            'email' => 'supervisor@example.test',
            'access_code' => 'scientific-code',
            'active' => true,
        ]);
        $assigned = Student::query()->forceCreate([
            'department_id' => $departmentId,
            'supervisor_id' => $supervisor->id,
            'fash_id' => $this->internshipPhase('Espa', '2025-2026', $departmentId),
            'fName' => 'ΔΙΚΟΣ',
            'lName' => 'ΦΟΙΤΗΤΗΣ',
            'AMF' => '1111111111111',
        ]);
        $other = Student::query()->forceCreate([
            'department_id' => $departmentId,
            'fash_id' => $assigned->fash_id,
            'fName' => 'ΑΛΛΟΣ',
            'lName' => 'ΦΟΙΤΗΤΗΣ',
            'AMF' => '2222222222222',
        ]);
        $assigned->actions()->create(['action' => 'finish_pa', 'status' => 'approved']);

        $this->post(route('scientific_supervisor.login'), ['access_code' => 'scientific-code'])
            ->assertRedirect(route('scientific_supervisor.dashboard'));

        $this->get('/')->assertRedirect(route('scientific_supervisor.dashboard'));

        $this->get(route('scientific_supervisor.dashboard'))
            ->assertOk()
            ->assertSee('ΦΟΙΤΗΤΗΣ ΔΙΚΟΣ')
            ->assertSee('2025-2026')
            ->assertSee('Χρηματοδότηση')
            ->assertSee('Αναζήτηση')
            ->assertSee('Ολοκληρωμένη')
            ->assertDontSee('ΦΟΙΤΗΤΗΣ ΑΛΛΟΣ');

        $this->get(route('scientific_supervisor.students.show', $assigned))
            ->assertOk()
            ->assertSee('Α. Πριν την έναρξη')
            ->assertSee('Στοιχεία πρακτικής')
            ->assertSee('Στοιχεία φοιτητή')
            ->assertSee('Στοιχεία φορέα')
            ->assertSee('Γενικά στοιχεία πρακτικής')
            ->assertSee('Αλλαγή κωδικού — σύντομα');

        $documentType = DocumentType::query()->where('code', 'symf_SN_f_f_ey')->firstOrFail();
        $assigned->documents()->create([
            'document_type_id' => $documentType->id,
            'version' => 1,
            'status' => 'submitted',
            'uploaded_by_role' => 'scientific_supervisor',
            'uploaded_by_id' => $supervisor->id,
        ]);

        $this->get(route('scientific_supervisor.students.show', ['student' => $assigned, 'panel' => 'documents']))
            ->assertOk()
            ->assertSee($documentType->title)
            ->assertSee('Προεπισκόπηση έτοιμης βεβαίωσης PDF');

        $this->get(route('scientific_supervisor.completion-certificate.preview', $assigned))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('scientific_supervisor.students.show', $other))->assertNotFound();
        $this->assertNotNull($supervisor->fresh()->last_login_at);
    }

    public function test_invalid_supervisor_code_is_rejected(): void
    {
        $this->post(route('scientific_supervisor.login'), ['access_code' => 'wrong'])
            ->assertSessionHasErrors('access_code');
    }

    public function test_logged_in_supervisor_is_redirected_from_another_roles_login_to_own_dashboard(): void
    {
        $departmentId = DB::table('departments')->insertGetId([
            'atlasId' => 993,
            'tmhma' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΤΜΗΜΑ REDIRECT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $supervisor = ScientificSupervisor::query()->forceCreate([
            'department_id' => $departmentId,
            'fName' => 'ΜΑΡΙΑ',
            'lName' => 'ΥΠΕΥΘΥΝΗ',
            'email' => 'supervisor@example.test',
            'access_code' => 'scientific-code',
            'active' => true,
        ]);

        $this->actingAs($supervisor, 'scientific_supervisor')
            ->get(route('provider.login'))
            ->assertRedirect(route('scientific_supervisor.dashboard'));
    }

    public function test_supervisor_cannot_upload_end_documents_before_section_b_is_complete(): void
    {
        $departmentId = DB::table('departments')->insertGetId([
            'atlasId' => 992,
            'tmhma' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΤΜΗΜΑ ΕΛΕΓΧΟΥ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $student = Student::query()->forceCreate([
            'department_id' => $departmentId,
            'fash_id' => $this->internshipPhase('Espa', '2025-2026', $departmentId),
            'fName' => 'ΔΟΚΙΜΗ',
            'lName' => 'ΜΠΛΟΚΑΡΙΣΜΑΤΟΣ',
            'AMF' => '3333333333333',
            'startDate' => now()->subMonths(3)->toDateString(),
            'endDate' => now()->subDay()->toDateString(),
        ]);
        $supervisor = ScientificSupervisor::query()->forceCreate([
            'department_id' => $departmentId,
            'fName' => 'ΜΑΡΙΑ',
            'lName' => 'ΥΠΕΥΘΥΝΗ',
            'email' => 'supervisor-blocked@example.test',
            'access_code' => 'blocked-code',
            'active' => true,
        ]);
        $student->forceFill(['supervisor_id' => $supervisor->id])->save();
        $documentType = DocumentType::query()->where('code', 'vevaiwsh_EY')->firstOrFail();
        $this->assertNotNull($documentType->workflowActions->firstWhere('funding_code', 'all'));
        $this->assertNotNull(WorkflowSectionRule::forContext('Espa', 'G', 'scientific_supervisor'));
        $this->assertFalse(app(WorkflowDisplay::class)->sectionIsComplete($student, 'B', 'scientific_supervisor'));
        $this->assertSame('previous_sections', $documentType->availabilityIssueFor($student, 'scientific_supervisor'));

        $this->actingAs($supervisor, 'scientific_supervisor')
            ->get(route('scientific_supervisor.students.show', ['student' => $student, 'panel' => 'documents']))
            ->assertOk()
            ->assertSee('Πρέπει πρώτα να ολοκληρωθούν οι ενέργειες Α και Β.');

        $this->post(route('scientific_supervisor.documents.store', [$student, $documentType]), [
            'file' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }
}
