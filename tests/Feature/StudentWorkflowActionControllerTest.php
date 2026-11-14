<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Models\WorkflowSectionRule;
use App\Support\ProviderQuestionnaireDefinition;
use App\Support\StudentApografikoDefinition;
use App\Support\StudentQuestionnaireDefinition;
use App\Support\WorkflowDisplay;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentWorkflowActionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_section_completion_is_calculated_from_its_actions(): void
    {
        $student = Student::forceCreate(['fash_id' => $this->internshipPhase('Institution'), 'startDate' => now()->subDay()]);

        $this->assertFalse(app(WorkflowDisplay::class)->sectionIsComplete($student, 'A'));
        $this->completeWorkflowSection($student, 'A');
        $this->assertTrue(app(WorkflowDisplay::class)->sectionIsComplete($student->fresh(), 'A'));
        $this->assertTrue(app(WorkflowDisplay::class)->sectionIsComplete($student->fresh(), 'B', 'student'));
    }

    public function test_rejecting_an_action_reopens_its_section(): void
    {
        $student = Student::forceCreate(['fash_id' => $this->internshipPhase('Institution')]);
        $this->completeWorkflowSection($student, 'A');
        $student->documents()->whereHas('type', fn ($query) => $query->where('code', 'symf_SN_FULL'))->update(['status' => 'rejected']);

        $this->assertFalse(app(WorkflowDisplay::class)->sectionIsComplete($student->fresh(), 'A'));
    }

    public function test_questionnaires_use_the_section_availability_rule(): void
    {
        $student = Student::forceCreate([
            'fash_id' => $this->internshipPhase('Institution'),
            'startDate' => now()->subMonth(),
            'endDate' => now()->addMonth(),
        ]);
        $this->completeWorkflowSection($student, 'A');

        $this->assertSame(
            'Το ερωτηματολόγιο ανοίγει μετά τη λήξη της πρακτικής.',
            app(StudentQuestionnaireDefinition::class)->unavailableReason($student->fresh()),
        );

        WorkflowSectionRule::where('funding_code', 'all')
            ->where('section', 'G')
            ->whereIn('role', ['student', 'provider'])
            ->update(['available_after' => 'always']);

        $this->assertNull(app(StudentQuestionnaireDefinition::class)->unavailableReason($student->fresh()));
        $this->assertNull(app(ProviderQuestionnaireDefinition::class)->unavailableReason($student->fresh()));
    }

    public function test_apografiko_uses_the_section_availability_rule(): void
    {
        $student = Student::forceCreate([
            'fash_id' => $this->internshipPhase('Espa'),
            'startDate' => now()->addMonth(),
            'endDate' => now()->addMonths(3),
        ]);
        $this->completeWorkflowSection($student, 'A');

        $this->assertSame(
            'Δεν έχει φτάσει ακόμη η ημερομηνία έναρξης της πρακτικής σας.',
            app(StudentApografikoDefinition::class)->unavailableReason($student->fresh(), 'input'),
        );

        WorkflowSectionRule::where('funding_code', 'Espa')
            ->where('section', 'B')
            ->where('role', 'student')
            ->update(['available_after' => 'always']);

        $this->assertNull(app(StudentApografikoDefinition::class)->unavailableReason($student->fresh(), 'input'));
    }

    public function test_payment_is_required_before_completing_the_internship(): void
    {
        $handler = User::factory()->create(['role' => 'admin']);
        $student = Student::forceCreate(['fash_id' => $this->internshipPhase('Espa'), 'startDate' => now()->subMonth(), 'endDate' => now()->subDay()]);
        $this->completeWorkflowSection($student, 'A');
        $this->completeWorkflowSection($student, 'B');
        $this->completeWorkflowSection($student, 'G');

        $this->actingAs($handler)
            ->post(route('students.workflow-actions.store', [$student, 'katastash_praktikis']), ['status' => 'completed'])
            ->assertRedirect()
            ->assertSessionHasErrors(['status' => 'Για να ολοκληρωθεί η πρακτική πρέπει πρώτα να ολοκληρωθεί η κατάσταση αιτήματος πληρωμής.']);
        $this->assertFalse($student->fresh()->isCompleted());
        $this->actingAs($handler)->post(route('students.workflow-actions.store', [$student, 'aitima_plhrwmhs']), ['status' => 'approved'])->assertRedirect();
        $this->actingAs($handler)->post(route('students.workflow-actions.store', [$student, 'katastash_praktikis']), ['status' => 'completed'])->assertRedirect();

        $this->assertTrue($student->fresh()->isCompleted());
    }

    public function test_handler_can_cancel_an_internship_without_completing_previous_sections(): void
    {
        $handler = User::factory()->create(['role' => 'admin']);
        $student = Student::forceCreate(['fash_id' => $this->internshipPhase('Espa')]);

        $this->actingAs($handler)->post(route('students.workflow-actions.store', [$student, 'katastash_praktikis']), [
            'status' => 'cancelled',
        ])->assertRedirect();

        $this->assertTrue($student->fresh()->isCancelled());
    }
}
