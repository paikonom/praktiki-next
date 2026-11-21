<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveStudentApografikoRequest;
use App\Models\Student;
use App\Models\StudentApografiko;
use App\Models\WorkflowAction;
use App\Services\OpsApografikoService;
use App\Support\StudentApografikoDefinition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StudentApografikoController extends Controller
{
    public function show(Request $request, string $type, StudentApografikoDefinition $definition): View
    {
        $student = $this->student($request);
        $this->assertType($type);
        $reason = $definition->unavailableReason($student, $type);
        $apografiko = $student->apografika->firstWhere('type', $type);
        abort_if($reason !== null && ! $apografiko?->isFinal(), 403, $reason);

        return view('student.apografiko', [
            'student' => $student,
            'type' => $type,
            'apografiko' => $apografiko,
            'legacyCompleted' => false,
            'formAnswers' => $definition->formAnswers($apografiko),
            'inputTrainingQuestions' => $definition->inputTrainingQuestions(),
            'educationQuestions' => $definition->educationQuestions(),
            'indicatorQuestions' => $definition->indicatorQuestions(),
            'outputQuestions' => $definition->outputQuestions(),
            'mis' => $student->phase?->praktiki?->opsMis(),
        ]);
    }

    public function store(
        SaveStudentApografikoRequest $request,
        string $type,
        StudentApografikoDefinition $definition,
        OpsApografikoService $ops,
    ): RedirectResponse {
        $this->assertType($type);
        $student = $request->user('student');
        $newStatus = $request->isDraft() ? StudentApografiko::STATUS_DRAFT : StudentApografiko::STATUS_SUBMITTED;
        $this->assertStatusAllowed($student, $type, $newStatus);
        $answers = $definition->normalizedAnswers($type, $request->submittedAnswers());

        try {
            [$apografiko, $payload] = DB::transaction(function () use ($student, $type, $newStatus, $answers, $ops) {
                // Lock the parent even when the form does not have a row yet.
                Student::query()->lockForUpdate()->findOrFail($student->id);
                $apografiko = $student->apografika()->firstOrNew(['type' => $type]);
                abort_if($apografiko->isFinal(), 409);

                if (in_array($apografiko->status, [StudentApografiko::STATUS_SUBMITTED, StudentApografiko::STATUS_PROCESSING], true)) {
                    return [$apografiko, null];
                }

                $apografiko->forceFill(['answers' => $answers, 'status' => $newStatus]);
                if ($newStatus === StudentApografiko::STATUS_DRAFT) {
                    $apografiko->save();

                    return [$apografiko, null];
                }

                $payload = $ops->buildSubmissionRequest($student, $apografiko);
                $apografiko->forceFill([
                    'request_payload' => $ops->sanitizedRequest($payload),
                    'response_payload' => null,
                    'request_progress_id' => null,
                    'deltio_id' => null,
                    'error_code' => null,
                    'error_message' => null,
                    'submitted_at' => now(),
                    'completed_at' => null,
                ])->save();

                return [$apografiko, $payload];
            });
        } catch (RuntimeException $exception) {
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface || $exception instanceof \PDOException) {
                throw $exception;
            }

            return $this->back($type, $exception->getMessage(), 'danger');
        }

        if ($payload === null) {
            return match ($apografiko->status) {
                StudentApografiko::STATUS_DRAFT => $this->back($type, 'Η προσωρινή αποθήκευση ολοκληρώθηκε.'),
                StudentApografiko::STATUS_PROCESSING => $this->resultRedirect($apografiko),
                default => $this->submissionPending($type),
            };
        }

        try {
            $result = $ops->send($payload, 'sentParticipants');
        } catch (Throwable $exception) {
            report($exception);
            $apografiko->forceFill([
                'error_code' => 'delivery_uncertain',
            ])->save();

            return $this->submissionPending($type);
        }

        if ($ops->isTemporaryFailure($result)) {
            $apografiko->forceFill([
                'response_payload' => $result['response'],
                'error_code' => 'delivery_uncertain',
            ])->save();

            return $this->submissionPending($type);
        }

        $this->applyResult($student, $apografiko, $result);

        return $this->resultRedirect($apografiko->fresh());
    }

    public function check(Request $request, string $type, OpsApografikoService $ops): RedirectResponse
    {
        $this->assertType($type);
        $student = $this->student($request);
        $apografiko = $student->apografika->firstWhere('type', $type);
        abort_unless($apografiko?->status === StudentApografiko::STATUS_PROCESSING && filled($apografiko->request_progress_id), 409);

        try {
            $payload = $ops->buildProgressRequest($apografiko->request_progress_id);
            $result = $ops->send($payload, 'getResponse');

            if ($ops->isTemporaryFailure($result)) {
                return $this->back($type, 'Δεν ήταν δυνατός ο έλεγχος αυτή τη στιγμή. Δοκιμάστε ξανά αργότερα.', 'danger');
            }
        } catch (Throwable $exception) {
            report($exception);

            return $this->back($type, 'Δεν ήταν δυνατός ο έλεγχος αυτή τη στιγμή. Δοκιμάστε ξανά αργότερα.', 'danger');
        }

        $this->applyResult($student, $apografiko, $result);

        return $this->resultRedirect($apografiko->fresh());
    }

    public function pdf(Request $request, string $type, StudentApografikoDefinition $definition): Response
    {
        $this->assertType($type);
        $student = $this->student($request)->loadMissing('department');
        $apografiko = $student->apografika->firstWhere('type', $type);
        abort_unless($apografiko?->status === StudentApografiko::STATUS_COMPLETED, 404);

        return Pdf::loadView('student.apografiko-pdf', [
            'student' => $student,
            'apografiko' => $apografiko,
            'questions' => $definition->answeredQuestions($apografiko),
            'mis' => $student->phase?->praktiki?->opsMis(),
        ])->setPaper('a4')->stream("apografiko-{$type}-{$student->id}.pdf");
    }

    /** @param array{status: string, response: string, request_progress_id?: string, deltio_id?: int, error_code?: string, error_message?: string} $result */
    private function applyResult(Student $student, StudentApografiko $apografiko, array $result): void
    {
        $this->assertStatusAllowed($student, $apografiko->type, $result['status']);
        $apografiko->forceFill([
            'status' => $result['status'],
            'response_payload' => $result['response'],
            'request_progress_id' => $result['request_progress_id'] ?? $apografiko->request_progress_id,
            'deltio_id' => $result['deltio_id'] ?? null,
            'error_code' => $result['error_code'] ?? null,
            'error_message' => $result['error_message'] ?? null,
            'completed_at' => $result['status'] === StudentApografiko::STATUS_COMPLETED ? now() : null,
        ])->save();
    }

    private function assertStatusAllowed(Student $student, string $type, string $status): void
    {
        abort_unless(
            WorkflowAction::statusIsAllowedFor($student, 'apografiko', $type, $status),
            422,
            "Η κατάσταση {$status} δεν επιτρέπεται για το απογραφικό {$type}.",
        );
    }

    private function resultRedirect(StudentApografiko $apografiko): RedirectResponse
    {
        return match ($apografiko->status) {
            StudentApografiko::STATUS_PROCESSING => $this->back($apografiko->type, 'Το ΟΠΣ παρέλαβε το δελτίο. Πατήστε «Έλεγχος αποτελέσματος» μετά από λίγα λεπτά.', 'warning'),
            StudentApografiko::STATUS_COMPLETED => $this->back($apografiko->type, 'Το απογραφικό δελτίο ολοκληρώθηκε επιτυχώς.'),
            default => $this->back($apografiko->type, 'Η υποβολή δεν ολοκληρώθηκε: '.($apografiko->error_message ?: 'Άγνωστο σφάλμα.'), 'danger'),
        };
    }

    private function back(string $type, string $message, string $statusType = 'success'): RedirectResponse
    {
        return redirect()->route('student.apografika.show', $type)
            ->with('status', $message)
            ->with('status_type', $statusType);
    }

    private function submissionPending(string $type): RedirectResponse
    {
        return $this->back($type, 'Η υποβολή είναι σε εξέλιξη ή δεν έχει επιβεβαιωθεί από το ΟΠΣ. Αν η κατάσταση παραμένει ίδια, επικοινωνήστε με το Γραφείο Πρακτικής Άσκησης.', 'warning');
    }

    private function student(Request $request): Student
    {
        /** @var Student $student */
        $student = $request->user('student');

        return $student->loadMissing(['phase.praktiki.funding', 'actions', 'documents.type', 'apografika']);
    }

    private function assertType(string $type): void
    {
        abort_unless(in_array($type, ['input', 'output'], true), 404);
    }
}
