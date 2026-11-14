<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitStudentQuestionnaireRequest;
use App\Models\Student;
use App\Models\StudentQuestionnaire;
use App\Models\WorkflowAction;
use App\Support\StudentQuestionnaireDefinition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class StudentQuestionnaireController extends Controller
{
    public function show(Request $request, StudentQuestionnaireDefinition $definition): View
    {
        $student = $this->student($request);
        $questionnaire = $student->questionnaire;
        $completed = $questionnaire?->isSubmitted() ?? false;
        $legacyCompleted = false;

        abort_unless($completed || $legacyCompleted || $definition->unavailableReason($student) === null, 403);

        $sections = $legacyCompleted ? [] : $definition->sections($student, $questionnaire?->version);
        $version = $questionnaire?->version ?? $definition->version($student);
        $answers = collect($questionnaire?->answers ?? []);

        return view('student.questionnaire', compact(
            'student', 'questionnaire', 'completed', 'legacyCompleted', 'sections', 'answers', 'version'
        ));
    }

    public function store(
        SubmitStudentQuestionnaireRequest $request,
        StudentQuestionnaireDefinition $definition,
    ): RedirectResponse {
        $student = $request->student();
        $questions = $request->questions();
        $answers = $request->validated('answers') ?? [];
        $isDraft = $request->isDraft();
        $newStatus = $isDraft ? StudentQuestionnaire::STATUS_DRAFT : StudentQuestionnaire::STATUS_SUBMITTED;
        abort_unless(WorkflowAction::statusIsAllowedFor($student, 'student_questionnaire', 'student', $newStatus), 422, 'Η κατάσταση δεν επιτρέπεται για το ερωτηματολόγιο φοιτητή.');

        DB::transaction(function () use ($student, $definition, $questions, $answers, $isDraft, $newStatus): void {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->id);
            abort_if($lockedStudent->questionnaire()->where('status', StudentQuestionnaire::STATUS_SUBMITTED)->exists(), 409);

            StudentQuestionnaire::updateOrCreate(
                ['s_id' => $student->id],
                [
                    'version' => $student->questionnaire?->version ?? $definition->version($student),
                    'answers' => $questions->mapWithKeys(fn ($question) => [
                        $question->code => $answers[$question->code] ?? null,
                    ])->all(),
                    'status' => $newStatus,
                    'submitted_at' => $isDraft ? null : now(),
                ],
            );
        });

        return redirect()->route('student.questionnaire')
            ->with('status', $isDraft
                ? 'Η προσωρινή αποθήκευση ολοκληρώθηκε. Μπορείτε να συνεχίσετε αργότερα.'
                : 'Το ερωτηματολόγιο υποβλήθηκε οριστικά.');
    }

    public function pdf(Request $request, StudentQuestionnaireDefinition $definition): Response
    {
        $student = $this->student($request);
        $questionnaire = $student->questionnaire;

        if (! $questionnaire?->isSubmitted()) {
            return redirect()->route('student.questionnaire')->with(
                'error',
                'Το ερωτηματολόγιο δεν έχει υποβληθεί οριστικά και το PDF δεν είναι διαθέσιμο.'
            );
        }

        $sections = $definition->sections($student, $questionnaire->version);
        $answers = collect($questionnaire->answers);
        $funding = $student->fundingCode();

        return Pdf::loadView('student.questionnaire-pdf', compact(
            'student', 'questionnaire', 'sections', 'answers', 'funding'
        ))
            ->setPaper('a4')
            ->stream('student-questionnaire-'.$student->id.'.pdf');
    }

    private function student(Request $request): Student
    {
        /** @var Student $student */
        $student = $request->user('student');

        return $student->loadMissing([
            'department', 'phase.praktiki.funding', 'provider', 'supervisor',
            'actions', 'questionnaire', 'documents.type',
        ]);
    }
}
