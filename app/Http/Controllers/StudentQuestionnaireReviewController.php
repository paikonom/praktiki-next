<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\WorkflowAction;
use App\Support\ProviderQuestionnaireDefinition;
use App\Support\StudentQuestionnaireDefinition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class StudentQuestionnaireReviewController extends Controller
{
    public function show(
        Student $student,
        string $type,
        StudentQuestionnaireDefinition $studentDefinition,
        ProviderQuestionnaireDefinition $providerDefinition,
    ): View {
        $this->authorizeStudent($student, $type);
        $student->load(['department', 'phase.praktiki.funding', 'provider', 'actions', 'questionnaire', 'providerQuestionnaire']);

        $questionnaire = $type === 'student' ? $student->questionnaire : $student->providerQuestionnaire;
        abort_unless($questionnaire, 404);

        $sections = $type === 'student'
            ? $studentDefinition->sections($student, $questionnaire->version)
            : $providerDefinition->sections($student, $questionnaire->version);

        return view('students.questionnaire', [
            'student' => $student,
            'type' => $type,
            'title' => $type === 'student' ? 'Ερωτηματολόγιο φοιτητή' : 'Ερωτηματολόγιο φορέα',
            'questionnaire' => $questionnaire,
            'sections' => $sections,
            'answers' => collect($questionnaire->answers),
        ]);
    }

    public function pdf(
        Student $student,
        string $type,
        StudentQuestionnaireDefinition $studentDefinition,
        ProviderQuestionnaireDefinition $providerDefinition,
    ): Response {
        $this->authorizeStudent($student, $type);
        $student->load([
            'department', 'phase.praktiki.funding', 'provider', 'supervisor',
            'actions', 'questionnaire', 'providerQuestionnaire',
        ]);

        $questionnaire = $type === 'student' ? $student->questionnaire : $student->providerQuestionnaire;
        abort_unless($questionnaire?->isSubmitted(), 404);

        $sections = $type === 'student'
            ? $studentDefinition->sections($student, $questionnaire->version)
            : $providerDefinition->sections($student, $questionnaire->version);
        $answers = collect($questionnaire->answers);
        $funding = $student->fundingCode();
        $view = $type === 'student' ? 'student.questionnaire-pdf' : 'provider.questionnaire-pdf';

        return Pdf::loadView($view, compact('student', 'questionnaire', 'sections', 'answers', 'funding'))
            ->setPaper('a4')
            ->stream($type.'-questionnaire-'.$student->id.'.pdf');
    }

    public function update(Request $request, Student $student, string $type): RedirectResponse
    {
        $this->authorizeStudent($student, $type);
        abort_unless(Gate::allows('update', $student), 403);
        $student->load(['phase.praktiki.funding', 'questionnaire', 'providerQuestionnaire']);

        $questionnaire = $type === 'student' ? $student->questionnaire : $student->providerQuestionnaire;
        abort_unless($questionnaire, 404);
        $targetStatus = $request->validate([
            'status' => ['required', 'in:submitted,rejected'],
        ])['status'];
        $allowedTransition = match ($questionnaire->status) {
            'submitted' => $targetStatus === 'rejected',
            'rejected' => $targetStatus === 'submitted',
            default => false,
        };
        abort_unless($allowedTransition, 409, 'Η αλλαγή κατάστασης δεν επιτρέπεται.');
        $sourceType = $type === 'student' ? 'student_questionnaire' : 'provider_questionnaire';
        abort_unless(
            WorkflowAction::statusIsAllowedFor($student, $sourceType, $type, $targetStatus),
            422,
            'Η κατάσταση δεν επιτρέπεται για αυτό το ερωτηματολόγιο.',
        );

        $questionnaire->update(['status' => $targetStatus]);

        $returnToOffice = in_array($request->input('from'), ['list', 'office'], true);

        return redirect()
            ->route($returnToOffice ? 'students.show' : 'students.questionnaires.show',
                $returnToOffice ? ['student' => $student, 'panel' => 'questionnaires'] : [$student, $type])
            ->with('status', $targetStatus === 'rejected'
                ? 'Το ερωτηματολόγιο απορρίφθηκε και μπορεί να υποβληθεί ξανά.'
                : 'Η απόρριψη του ερωτηματολογίου αναιρέθηκε.');
    }

    private function authorizeStudent(Student $student, string $type): void
    {
        abort_unless(in_array($type, ['student', 'provider'], true), 404);
        abort_unless(Gate::allows('view', $student), 404);
    }
}
