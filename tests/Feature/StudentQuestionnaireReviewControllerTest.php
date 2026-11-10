<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentQuestionnaireReviewControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_review_both_questionnaires_and_their_pdfs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->studentWithQuestionnaires();

        $questionnairesResponse = $this->actingAs($admin)
            ->get(route('students.show', ['student' => $student, 'panel' => 'questionnaires']))
            ->assertOk()
            ->assertSee('Αξιολόγηση Συνολικής Εμπειρίας Πρακτικής Άσκησης από τον/τη Φοιτητή/τρια')
            ->assertSee('Αξιολόγηση Φοιτητή/τριας Πρακτικής Άσκησης από τον/την αρμόδιο/α Επόπτη/τρια')
            ->assertSee('Απόρριψη');
        preg_match_all('/>\s*Απόρριψη\s*<\/button>/', $questionnairesResponse->getContent(), $rejectButtons);
        $this->assertCount(2, $rejectButtons[0]);

        $this->actingAs($admin)
            ->get(route('students.questionnaires.pdf', [$student, 'student']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get(route('students.questionnaires.pdf', [$student, 'provider']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->patch(route('students.questionnaires.update', [$student, 'student']), ['status' => 'rejected'])
            ->assertRedirect(route('students.show', ['student' => $student, 'panel' => 'questionnaires']));

        $this->assertDatabaseHas('student_questionnaires', [
            's_id' => $student->id,
            'status' => 'rejected',
        ]);
        $this->assertSame(
            'Δοκιμαστική έκθεση φοιτητή',
            $student->fresh()->questionnaire->answers['comm'],
        );

        $this->actingAs($admin)
            ->get(route('students.show', ['student' => $student, 'panel' => 'questionnaires']))
            ->assertOk()
            ->assertSeeInOrder(['Αξιολόγηση Συνολικής Εμπειρίας Πρακτικής Άσκησης από τον/τη Φοιτητή/τρια', 'Απορρίφθηκε', 'Αναίρεση απόρριψης']);

        $this->actingAs($admin)
            ->patch(route('students.questionnaires.update', [$student, 'student']), ['status' => 'submitted'])
            ->assertRedirect(route('students.show', ['student' => $student, 'panel' => 'questionnaires']));

        $this->assertDatabaseHas('student_questionnaires', [
            's_id' => $student->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($admin)
            ->patch(route('students.questionnaires.update', [$student, 'provider']), ['status' => 'rejected', 'from' => 'list'])
            ->assertRedirect(route('students.show', ['student' => $student, 'panel' => 'questionnaires']));

        $this->assertDatabaseHas('provider_questionnaires', [
            's_id' => $student->id,
            'status' => 'rejected',
        ]);
    }

    public function test_user_cannot_review_questionnaires_outside_their_departments(): void
    {
        $user = User::factory()->create();
        $student = $this->studentWithQuestionnaires();

        $this->actingAs($user)
            ->get(route('students.show', ['student' => $student, 'panel' => 'questionnaires']))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('students.questionnaires.pdf', [$student, 'provider']))
            ->assertNotFound();
    }

    public function test_reject_is_hidden_and_blocked_when_the_workflow_does_not_allow_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->studentWithQuestionnaires();
        DB::table('workflow_actions')
            ->where('source_type', 'student_questionnaire')
            ->where('source_key', 'student')
            ->update(['allowed_statuses' => json_encode(['draft', 'submitted'])]);

        $response = $this->actingAs($admin)
            ->get(route('students.show', ['student' => $student, 'panel' => 'questionnaires']))
            ->assertOk();
        preg_match_all('/>\s*Απόρριψη\s*<\/button>/', $response->getContent(), $rejectButtons);
        $this->assertCount(1, $rejectButtons[0]);

        $this->actingAs($admin)
            ->patch(route('students.questionnaires.update', [$student, 'student']), ['status' => 'rejected'])
            ->assertUnprocessable();
    }

    private function studentWithQuestionnaires(): Student
    {
        $departmentId = DB::table('departments')->insertGetId([
            'atlasId' => 100,
            'tmhma' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΤΜΗΜΑ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $studentId = DB::table('students')->insertGetId([
            'department_id' => $departmentId,
            'fName' => 'ΜΑΡΙΑ',
            'lName' => 'ΔΟΚΙΜΗ',
            'fash_id' => $this->internshipPhase('Espa', '2025-2026', $departmentId),
        ]);
        $providerId = DB::table('providers')->insertGetId([
            's_id' => $studentId,
            'f_epwnymia' => 'ΔΟΚΙΜΑΣΤΙΚΟΣ ΦΟΡΕΑΣ',
        ]);
        DB::table('student_questionnaires')->insert([
            's_id' => $studentId,
            'version' => 1,
            'status' => 'submitted',
            'answers' => json_encode(['comm' => 'Δοκιμαστική έκθεση φοιτητή', 'A1' => 'ΠΑΡΑ ΠΟΛΥ'], JSON_UNESCAPED_UNICODE),
            'submitted_at' => '2026-01-01 12:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('provider_questionnaires')->insert([
            's_id' => $studentId,
            'p_id' => $providerId,
            'version' => 1,
            'status' => 'submitted',
            'answers' => json_encode(['comm' => 'Δοκιμαστική έκθεση φορέα', 'A1' => 'ΝΑΙ'], JSON_UNESCAPED_UNICODE),
            'submitted_at' => '2026-01-02 12:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Student::findOrFail($studentId);
    }
}
