<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitProviderQuestionnaireRequest;
use App\Models\ProviderQuestionnaire;
use App\Models\Student;
use App\Models\WorkflowAction;
use App\Support\ProviderQuestionnaireDefinition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProviderQuestionnaireController extends Controller
{
    public function show(Request $request, ProviderQuestionnaireDefinition $definition): View|RedirectResponse
    {
        $student = $this->student($request);
        $questionnaire = $student->providerQuestionnaire;
        $completed = $questionnaire?->isSubmitted() ?? false;
        $legacyCompleted = false;

        $unavailableReason = $definition->unavailableReason($student);
        if (! $completed && ! $legacyCompleted && $unavailableReason) {
            return redirect()->route('provider.dashboard')->with('error', $unavailableReason);
        }

        return view('provider.questionnaire', [
            'student' => $student,
            'questionnaire' => $questionnaire,
            'completed' => $completed,
            'legacyCompleted' => $legacyCompleted,
            'sections' => $legacyCompleted ? [] : $definition->sections($student, $questionnaire?->version ?? 1),
            'answers' => collect($questionnaire?->answers ?? []),
        ]);
    }

    public function store(SubmitProviderQuestionnaireRequest $request): RedirectResponse
    {
        $student = $request->student();
        $questions = $request->questions();
        $answers = $request->validated('answers') ?? [];
        $isDraft = $request->isDraft();
        $newStatus = $isDraft ? ProviderQuestionnaire::STATUS_DRAFT : ProviderQuestionnaire::STATUS_SUBMITTED;
        abort_unless(WorkflowAction::statusIsAllowedFor($student, 'provider_questionnaire', 'provider', $newStatus), 422, 'Η κατάσταση δεν επιτρέπεται για το ερωτηματολόγιο φορέα.');

        DB::transaction(function () use ($student, $questions, $answers, $isDraft, $newStatus): void {
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->id);
            abort_if($lockedStudent->providerQuestionnaire()->where('status', ProviderQuestionnaire::STATUS_SUBMITTED)->exists(), 409);

            ProviderQuestionnaire::updateOrCreate(
                ['s_id' => $student->id],
                [
                    'p_id' => $student->provider->id,
                    'version' => 1,
                    'answers' => $questions->mapWithKeys(fn ($question) => [
                        $question->code => $answers[$question->code] ?? null,
                    ])->all(),
                    'status' => $newStatus,
                    'submitted_at' => $isDraft ? null : now(),
                ],
            );
        });

        return redirect()->route('provider.questionnaire')->with('status', $isDraft
            ? 'Η προσωρινή αποθήκευση ολοκληρώθηκε. Μπορείτε να συνεχίσετε αργότερα.'
            : 'Το ερωτηματολόγιο υποβλήθηκε οριστικά.');
    }

    public function pdf(Request $request, ProviderQuestionnaireDefinition $definition): Response
    {
        $student = $this->student($request);
        $questionnaire = $student->providerQuestionnaire;
        if (! $questionnaire?->isSubmitted()) {
            return redirect()->route('provider.questionnaire')->with(
                'error',
                'Το ερωτηματολόγιο δεν έχει υποβληθεί οριστικά και το PDF δεν είναι διαθέσιμο.'
            );
        }

        $sections = $definition->sections($student, $questionnaire->version);
        $answers = collect($questionnaire->answers);
        $funding = $student->fundingCode();

        return Pdf::loadView('provider.questionnaire-pdf', compact('student', 'questionnaire', 'sections', 'answers', 'funding'))
            ->setPaper('a4')
            ->stream('provider-questionnaire-'.$student->id.'.pdf');
    }

    private function student(Request $request): Student
    {
        /** @var Student $student */
        $student = $request->user('provider');

        return $student->loadMissing([
            'department', 'phase.praktiki.funding', 'provider', 'supervisor',
            'actions', 'providerQuestionnaire',
        ]);
    }
}
