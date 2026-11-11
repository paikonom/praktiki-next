<?php

namespace Tests\Feature;

use App\Mail\WorkflowEmail;
use App\Models\Student;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Services\StudentEmailService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentEmailControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_handler_previews_and_sends_a_known_email_while_recording_the_log(): void
    {
        [$user, $student] = $this->handlerAndStudent();
        Mail::fake();

        $this->actingAs($user)
            ->get(route('students.emails.preview', [$student, 'stoixeiaFoit']))
            ->assertOk()
            ->assertSee('student@example.test')
            ->assertSee('Με αυτό το email θα θέλαμε να σας καλωσορίσουμε');

        $this->actingAs($user)
            ->post(route('students.emails.store', [$student, 'stoixeiaFoit']))
            ->assertRedirect(route('students.show', ['student' => $student, 'panel' => 'emails']));

        Mail::assertSent(WorkflowEmail::class, fn (WorkflowEmail $mail): bool => $mail->hasTo('student@example.test')
            && $mail->hasCc('handler@example.test')
            && $mail->hasCc('paoikon@uoa.gr'));
        $this->assertDatabaseHas('email_logs', [
            'student_id' => $student->id,
            'sent_by_user_id' => $user->id,
            'type' => 'stoixeiaFoit',
            'status' => 'sent',
        ]);

        $this->actingAs($user)
            ->get(route('students.show', ['student' => $student, 'panel' => 'emails']))
            ->assertOk()
            ->assertSee('Emails / Log')
            ->assertSee('(1)');
    }

    public function test_server_rejects_an_email_when_its_workflow_requirement_is_missing(): void
    {
        [$user, $student] = $this->handlerAndStudent();
        Mail::fake();

        $this->actingAs($user)
            ->post(route('students.emails.store', [$student, 'stoixeiaFoitEgkrish']))
            ->assertSessionHasErrors(['email' => 'Απαιτείται πρώτα έγκριση στοιχείων.']);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_completed_tasks_disable_their_reminder_emails(): void
    {
        [, $student] = $this->handlerAndStudent();
        $emails = app(StudentEmailService::class);

        $this->assertSame('Δεν έχει φτάσει η ημερομηνία λήξης.', $emails->unavailableReason($student, 'lhxh_ey'));

        $student->forceFill(['endDate' => now()->subDay()])->save();
        $student->apografika()->create(['type' => 'input', 'status' => 'completed', 'answers' => []]);

        $this->assertSame('Το απογραφικό εισόδου έχει ήδη ολοκληρωθεί.', $emails->unavailableReason($student, 'input'));
        $this->assertNull($emails->unavailableReason($student, 'lhxh_ey'));

        $this->completeWorkflowSection($student, 'G');
        $this->assertSame('Οι ενέργειες λήξης του φοιτητή έχουν ήδη ολοκληρωθεί.', $emails->unavailableReason($student, 'lhxh'));
        $this->assertSame('Οι ενέργειες λήξης του φορέα έχουν ήδη ολοκληρωθεί.', $emails->unavailableReason($student, 'lhxh_foreas'));
    }

    public function test_ajax_send_returns_json_with_the_updated_count(): void
    {
        [$user, $student] = $this->handlerAndStudent();
        Mail::fake();

        $this->actingAs($user)
            ->postJson(route('students.emails.store', [$student, 'stoixeiaFoit']))
            ->assertOk()
            ->assertJson([
                'message' => 'Το email στάληκε επιτυχώς.',
                'count' => 1,
            ]);

        Mail::assertSent(WorkflowEmail::class, 1);
    }

    public function test_view_only_handler_cannot_preview_or_send_email(): void
    {
        [$user, $student] = $this->handlerAndStudent(['view_only' => true]);
        Mail::fake();

        $this->actingAs($user)
            ->get(route('students.emails.preview', [$student, 'stoixeiaFoit']))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('students.emails.store', [$student, 'stoixeiaFoit']))
            ->assertForbidden();

        Mail::assertNothingSent();
        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_every_legacy_manual_email_template_renders(): void
    {
        [$user, $student] = $this->handlerAndStudent();
        $student->load(['department', 'provider', 'supervisor', 'phase.praktiki.funding']);
        $emails = app(StudentEmailService::class);

        foreach (['stoixeiaFoit', 'stoixeiaFoitEgkrish', 'symf_SN_f_f_ey', 'symf_SN_FULL', 'erganh_enarxh', 'input', 'lhxh', 'lhxh_foreas', 'lhxh_ey'] as $type) {
            $email = $emails->prepare($student, $type);
            $body = (new WorkflowEmail($email['subject'], $email['data']))->render();

            $this->assertNotEmpty($body, $type);
            $this->assertStringContainsString('Γραφείο Πρακτικής Άσκησης ΕΚΠΑ', $body, $type);
        }

        $externalEmail = $emails->prepare($student, 'symf_SN_f_f_ey');
        $this->assertStringContainsString(
            'ΕΚΚΡΕΜΕΙ: νέος εξωτερικός σύνδεσμος',
            (new WorkflowEmail($externalEmail['subject'], $externalEmail['data']))->render(),
        );
    }

    public function test_pending_actions_choose_the_relevant_emails(): void
    {
        $emails = app(StudentEmailService::class);
        $action = fn (string $code, string $role): WorkflowAction => (new WorkflowAction)->forceFill([
            'code' => $code,
            'responsible_role' => $role,
        ]);

        $this->assertSame(['input'], $emails->typesForPendingActions('B', collect([
            $action('input_sumpl', 'student'),
            $action('input_upload', 'student'),
        ])));
        $this->assertSame(['lhxh', 'lhxh_foreas', 'lhxh_ey'], $emails->typesForPendingActions('G', collect([
            $action('student_task', 'student'),
            $action('provider_task', 'provider'),
            $action('supervisor_task', 'scientific_supervisor'),
        ])));
        $this->assertSame(['lhxh_foreas', 'lhxh_ey'], $emails->typesForPendingActions('G', collect([
            $action('provider_task', 'provider'),
            $action('supervisor_task', 'scientific_supervisor'),
        ])));
        $this->assertSame(['lhxh_ey'], $emails->typesForPendingActions('G', collect([
            $action('supervisor_task', 'scientific_supervisor'),
        ])));
        $this->assertSame([], $emails->typesForPendingActions('G', collect([
            $action('handler_task', 'member'),
        ])));
    }

    /** @return array{User, Student} */
    private function handlerAndStudent(array $userAttributes = []): array
    {
        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 901, 'tmhma' => 'Δοκιμαστικό Τμήμα']);
        $phaseId = $this->internshipPhase('Espa', '2025-2026', $departmentId);
        $supervisorId = DB::table('scientific_supervisors')->insertGetId([
            'department_id' => $departmentId,
            'fName' => 'ΕΠΙΣΤΗΜΟΝΙΚΟΣ',
            'lName' => 'ΥΠΕΥΘΥΝΟΣ',
            'email' => 'supervisor@example.test',
        ]);
        $studentId = DB::table('students')->insertGetId([
            'department_id' => $departmentId,
            'fash_id' => $phaseId,
            'supervisor_id' => $supervisorId,
            'fName' => 'ΔΟΚΙΜΑΣΤΙΚΗ',
            'lName' => 'ΦΟΙΤΗΤΡΙΑ',
            'AMF' => '12345',
            'email' => 'student@example.test',
            'startDate' => now()->subMonth(),
            'endDate' => now()->addMonth(),
        ]);
        DB::table('providers')->insert([
            's_id' => $studentId,
            'f_email' => 'provider@example.test',
            'f_epopt_email' => 'provider-supervisor@example.test',
        ]);
        $user = User::factory()->create($userAttributes + ['email' => 'handler@example.test']);
        DB::table('user_department_access')->insert([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'can_manage_espa' => true,
        ]);

        $student = Student::findOrFail($studentId);
        $this->assertTrue($student->handler()?->is($user));

        return [$user, $student];
    }
}
