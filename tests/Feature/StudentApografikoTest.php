<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentApografiko;
use App\Models\User;
use App\Support\StudentApografikoDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentApografikoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $codes = [
            57 => 'Β', 22 => 'Β1', 98 => 'Β2', 25 => 'Β3', 26 => 'Β4', 116 => 'Β5', 111 => 'Β',
            27 => 'Γ1', 28 => 'Γ2', 29 => 'Γ3', 99 => 'Γ4', 100 => 'Γ5', 101 => 'Γ6', 32 => 'Γ7', 33 => 'Γ8', 34 => 'Γ9',
            117 => 'Δ1', 102 => 'Δ.1.Π', 112 => 'Δ2', 113 => 'Δ.2.α', 114 => 'Δ.2.β', 115 => 'Δ.2.γ', 38 => 'Δ3', 39 => 'Δ4', 40 => 'Δ5', 49 => 'Δ6',
        ];

        DB::table('espaQuestions_new')->insert(collect($codes)->map(fn (string $code, int $id): array => [
            'id_microdata' => $id,
            'kodikos_erotimatos' => $code,
            'perigrafi' => $id === 25 ? 'Φοιτητής/τρια τριτοβάθμιας εκπαίδευσης πλήρους φοίτησης' : "Ερώτηση {$code}",
            'id_patera' => null,
            'epipedo' => in_array($id, [22, 98, 25, 26, 116], true) ? 2 : 1,
            'epexhghsh' => null,
        ])->values()->all());
    }

    public function test_student_submits_input_and_completes_it_after_ops_processing(): void
    {
        config()->set('praktiki_apografika.enabled', true);
        config()->set('praktiki_apografika.endpoint', 'https://ops.example.test/participants');
        config()->set('praktiki_apografika.username', 'test-user');
        config()->set('praktiki_apografika.password', 'test-password');
        config()->set('praktiki_apografika.mis_override', '6020906');
        $student = $this->eligibleStudent();

        Http::fake(fn ($request) => str_contains($request->body(), 'RequestProgressID')
            ? Http::response($this->completedResponse(3207964), 200)
            : Http::response($this->progressResponse('REQ-123456'), 200));

        $this->actingAs($student, 'student')
            ->get(route('student.apografika.show', 'input'))
            ->assertOk()
            ->assertSee('Απογραφικό δελτίο εισόδου')
            ->assertSee('Απαντήσεις δελτίου εισόδου')
            ->assertSee('ΔΕΝ ΑΠΑΝΤΩ')
            ->assertSee('Προσωρινή αποθήκευση');

        $this->actingAs($student, 'student')
            ->post(route('student.apografika.store', 'input'), $this->inputAnswers())
            ->assertRedirect(route('student.apografika.show', 'input'))
            ->assertSessionHas('status_type', 'warning');

        $apografiko = StudentApografiko::query()->whereBelongsTo($student)->where('type', 'input')->firstOrFail();
        $this->assertSame(StudentApografiko::STATUS_PROCESSING, $apografiko->status);
        $this->assertSame('REQ-123456', $apografiko->request_progress_id);
        $this->assertStringContainsString('<urn:IDOfelDikaiouxou>1234567890123</urn:IDOfelDikaiouxou>', $apografiko->request_payload);
        $this->assertStringContainsString('<urn:IDPeriferias>55</urn:IDPeriferias>', $apografiko->request_payload);
        $this->assertStringContainsString('[REDACTED]', $apografiko->request_payload);
        $this->assertStringNotContainsString('ΑΒ123456', $apografiko->request_payload);
        $this->assertStringStartsWith('<?xml', DB::table('student_apografika')->where('id', $apografiko->id)->value('request_payload'));

        $response = $this->actingAs($student->fresh(), 'student')
            ->post(route('student.apografika.check', 'input'))
            ->assertRedirect(route('student.apografika.show', 'input'));

        $this->assertDatabaseHas('student_apografika', [
            'student_id' => $student->id,
            'type' => 'input',
            'status' => StudentApografiko::STATUS_COMPLETED,
            'deltio_id' => 3207964,
        ]);
        $this->assertSame(
            $this->completedResponse(3207964),
            DB::table('student_apografika')->where('id', $apografiko->id)->value('response_payload'),
        );
        $response->assertSessionHas('status_type', 'success');
    }

    public function test_student_can_save_and_clear_an_input_draft(): void
    {
        $student = $this->eligibleStudent();

        $this->actingAs($student, 'student')
            ->post(route('student.apografika.store', 'input'), [
                'submission' => 'draft',
                'answers' => [
                    'education_level' => '100',
                    'indicators' => ['117' => '5321'],
                ],
            ])
            ->assertRedirect(route('student.apografika.show', 'input'));

        $apografiko = StudentApografiko::query()->whereBelongsTo($student)->firstOrFail();
        $this->assertSame('5321', $apografiko->answers[100]);
        $this->assertSame('5321', $apografiko->answers[117]);

        $this->actingAs($student->fresh(), 'student')
            ->post(route('student.apografika.store', 'input'), [
                'submission' => 'draft',
                'answers' => [],
            ])
            ->assertRedirect(route('student.apografika.show', 'input'));

        $this->assertNull($apografiko->fresh()->answers[100]);
        $this->assertNull($apografiko->fresh()->answers[117]);
    }

    public function test_output_shows_the_six_legacy_questions_and_sends_only_their_fixed_answers(): void
    {
        $student = $this->eligibleStudent();
        $student->forceFill(['endDate' => now()->subDay()])->save();
        StudentApografiko::create([
            'student_id' => $student->id,
            'type' => 'input',
            'status' => StudentApografiko::STATUS_COMPLETED,
        ]);
        $this->completeWorkflowSection($student, 'B');

        $this->actingAs($student, 'student')
            ->get(route('student.apografika.show', 'output'))
            ->assertOk()
            ->assertSee('Β1')
            ->assertSee('Β5')
            ->assertSee('Φοιτητής/τρια τριτοβάθμιας εκπαίδευσης πλήρους φοίτησης');

        $answers = app(StudentApografikoDefinition::class)->normalizedAnswers('output', []);

        $this->assertSame([111, 22, 98, 25, 26, 116], array_keys($answers));
        $this->assertArrayNotHasKey(51, $answers);
    }

    public function test_handler_can_mark_an_unneeded_output_and_cancel_a_completed_input(): void
    {
        $handler = User::factory()->create(['role' => 'admin']);
        $student = $this->eligibleStudent();
        StudentApografiko::create([
            'student_id' => $student->id,
            'type' => 'input',
            'status' => StudentApografiko::STATUS_COMPLETED,
            'deltio_id' => 470806,
        ]);

        $this->actingAs($handler)
            ->patch(route('students.apografika.update', [$student, 'output']), ['status' => 'not_required'])
            ->assertRedirect(route('students.show', ['student' => $student, 'panel' => 'questionnaires']));
        $this->actingAs($handler)
            ->patch(route('students.apografika.update', [$student, 'input']), ['status' => 'cancelled'])
            ->assertRedirect(route('students.show', ['student' => $student, 'panel' => 'questionnaires']));

        $this->assertDatabaseHas('student_apografika', ['student_id' => $student->id, 'type' => 'output', 'status' => 'not_required']);
        $this->assertDatabaseHas('student_documents', [
            's_id' => $student->id,
            'document_type_id' => DocumentType::where('code', 'output')->value('id'),
            'status' => 'not_required',
        ]);
        $this->assertDatabaseHas('student_apografika', [
            'student_id' => $student->id,
            'type' => 'input',
            'status' => 'cancelled',
            'deltio_id' => 470806,
            'cancelled_by' => $handler->id,
        ]);

        $this->actingAs($handler)
            ->patch(route('students.apografika.update', [$student, 'input']), ['status' => 'completed'])
            ->assertRedirect(route('students.show', ['student' => $student, 'panel' => 'questionnaires']));
        $this->actingAs($handler)
            ->patch(route('students.apografika.update', [$student, 'output']), ['status' => 'draft'])
            ->assertRedirect(route('students.show', ['student' => $student, 'panel' => 'questionnaires']));

        $this->assertDatabaseHas('student_apografika', [
            'student_id' => $student->id,
            'type' => 'input',
            'status' => 'completed',
            'cancelled_at' => null,
            'cancelled_by' => null,
        ]);
        $this->assertDatabaseHas('student_apografika', ['student_id' => $student->id, 'type' => 'output', 'status' => 'draft']);
        $this->assertDatabaseMissing('student_documents', [
            's_id' => $student->id,
            'document_type_id' => DocumentType::where('code', 'output')->value('id'),
            'status' => 'not_required',
        ]);
    }

    public function test_cancellation_is_hidden_and_blocked_when_the_workflow_does_not_allow_it(): void
    {
        $handler = User::factory()->create(['role' => 'admin']);
        $student = $this->eligibleStudent();
        StudentApografiko::create([
            'student_id' => $student->id,
            'type' => 'input',
            'status' => StudentApografiko::STATUS_COMPLETED,
        ]);
        DB::table('workflow_actions')
            ->where('source_type', 'apografiko')
            ->where('source_key', 'input')
            ->update(['allowed_statuses' => json_encode(['draft', 'submitted', 'processing', 'completed', 'failed', 'not_required'])]);

        $this->actingAs($handler)
            ->get(route('students.show', ['student' => $student, 'panel' => 'questionnaires']))
            ->assertOk()
            ->assertDontSee('Καταχώριση ακύρωσης');

        $this->actingAs($handler)
            ->get(route('students.show', ['student' => $student, 'panel' => 'office']))
            ->assertOk()
            ->assertDontSee('Καταχώριση ακύρωσης');

        $this->actingAs($handler)
            ->patch(route('students.apografika.update', [$student, 'input']), ['status' => 'cancelled'])
            ->assertUnprocessable();
    }

    public function test_completed_apografiko_has_a_pdf_from_its_stored_answers(): void
    {
        $student = $this->eligibleStudent();
        StudentApografiko::create([
            'student_id' => $student->id,
            'type' => 'input',
            'status' => StudentApografiko::STATUS_COMPLETED,
            'answers' => [57 => '5321', 22 => '5322'],
            'deltio_id' => 470806,
            'submitted_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($student->fresh(), 'student')
            ->get(route('student.apografika.pdf', 'input'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /** @return array<string, mixed> */
    private function inputAnswers(): array
    {
        return [
            'submission' => 'final',
            'answers' => [
                'education_level' => '100',
                'indicators' => app(StudentApografikoDefinition::class)->indicatorQuestions()->keys()
                    ->mapWithKeys(fn (int $id): array => [(string) $id => '5322'])
                    ->all(),
            ],
        ];
    }

    private function eligibleStudent(): Student
    {
        $phaseId = $this->internshipPhase();
        $departmentId = DB::table('praktiki_phases')->where('id', $phaseId)->value('department_id');
        $student = Student::query()->forceCreate([
            'department_id' => $departmentId,
            'fash_id' => $phaseId,
            'AMF' => '1234567890123',
            'fName' => 'ΔΟΚΙΜΗ',
            'lName' => 'ΦΟΙΤΗΤΗΣ',
            'afm' => '123456789',
            'adt' => 'ΑΒ123456',
            'fylo_rescom' => 1,
            'startDate' => now()->subDay(),
            'endDate' => now()->addMonth(),
        ]);
        $this->approveStudentDetails($student->id);
        DB::table('student_actions')->insert([
            ['student_id' => $student->id, 'action' => 'section_a_approval', 'status' => 'approved'],
            ['student_id' => $student->id, 'action' => 'section_b_approval', 'status' => 'approved'],
        ]);

        foreach (['symf_initial', 'symf_SN_f_f', 'symf_SN_FULL'] as $code) {
            $type = DocumentType::query()->where('code', $code)->firstOrFail();
            DB::table('student_documents')->insert([
                's_id' => $student->id,
                'document_type_id' => $type->id,
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
                'reviewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->completeWorkflowSection($student, 'A');

        return $student->fresh();
    }

    private function progressResponse(string $progressId): string
    {
        return <<<XML
        <env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:espa:v6:services:participants">
          <env:Body><urn:SymetexontesResponse><urn:RequestProgressMessage>χρησιμοποιείστε το {$progressId}</urn:RequestProgressMessage></urn:SymetexontesResponse></env:Body>
        </env:Envelope>
        XML;
    }

    private function completedResponse(int $deltioId): string
    {
        return <<<XML
        <env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:espa:v6:services:participants">
          <env:Body><urn:SymetexontesResponse><urn:OfeloumenosOutput><urn:DeltioOfeloumenou><urn:ErrorCode>0</urn:ErrorCode><urn:ErrorMessage>Δημιουργήθηκε νέο Δελτίο με Α/Α: {$deltioId} (6020906)</urn:ErrorMessage><urn:KodikosMis>6020906</urn:KodikosMis><urn:EidosDeltiou>1</urn:EidosDeltiou></urn:DeltioOfeloumenou></urn:OfeloumenosOutput></urn:SymetexontesResponse></env:Body>
        </env:Envelope>
        XML;
    }
}
