<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentApografiko;
use App\Models\WorkflowAction;
use App\Services\StudentDocumentService;
use App\Support\StudentApografikoDefinition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class StudentApografikoReviewController extends Controller
{
    public function pdf(Student $student, string $type, StudentApografikoDefinition $definition): Response
    {
        $this->authorizeRequest($student, $type);
        $student->load(['department', 'phase.praktiki.funding', 'apografika']);
        $apografiko = $student->apografika->firstWhere('type', $type);
        abort_unless($apografiko?->status === StudentApografiko::STATUS_COMPLETED, 404);

        return Pdf::loadView('student.apografiko-pdf', [
            'student' => $student,
            'apografiko' => $apografiko,
            'questions' => $definition->answeredQuestions($apografiko),
            'mis' => config('praktiki_apografika.mis_override') ?: data_get($student->phase?->praktiki?->details, 'kwdikos_OPS'),
        ])->setPaper('a4')->stream("apografiko-{$type}-{$student->id}.pdf");
    }

    public function update(Request $request, Student $student, string $type, StudentDocumentService $documents): RedirectResponse
    {
        $this->authorizeRequest($student, $type);
        $data = $request->validate([
            'status' => ['required', Rule::in([
                StudentApografiko::STATUS_CANCELLED,
                StudentApografiko::STATUS_NOT_REQUIRED,
                StudentApografiko::STATUS_COMPLETED,
                StudentApografiko::STATUS_DRAFT,
            ])],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $student->loadMissing('phase.praktiki.funding');
        abort_unless(
            WorkflowAction::statusIsAllowedFor($student, 'apografiko', $type, $data['status']),
            422,
            'Η κατάσταση δεν επιτρέπεται για αυτό το απογραφικό.',
        );
        $apografiko = $student->apografika()->where('type', $type)->first();

        $currentStatus = $apografiko?->status;
        $allowedTransition = match ($data['status']) {
            StudentApografiko::STATUS_CANCELLED => $currentStatus === StudentApografiko::STATUS_COMPLETED,
            StudentApografiko::STATUS_COMPLETED => $currentStatus === StudentApografiko::STATUS_CANCELLED,
            StudentApografiko::STATUS_DRAFT => $currentStatus === StudentApografiko::STATUS_NOT_REQUIRED,
            StudentApografiko::STATUS_NOT_REQUIRED => ! in_array($currentStatus, [
                StudentApografiko::STATUS_COMPLETED,
                StudentApografiko::STATUS_CANCELLED,
                StudentApografiko::STATUS_NOT_REQUIRED,
            ], true),
            default => false,
        };
        abort_unless($allowedTransition, 409, 'Η αλλαγή κατάστασης δεν επιτρέπεται.');

        if ($data['status'] === StudentApografiko::STATUS_NOT_REQUIRED) {
            $apografiko ??= $student->apografika()->create(['type' => $type, 'status' => StudentApografiko::STATUS_DRAFT]);
        }

        DB::transaction(function () use ($apografiko, $data, $request, $student, $type, $documents): void {
            $apografiko->forceFill([
                'status' => $data['status'],
                'cancelled_at' => $data['status'] === StudentApografiko::STATUS_CANCELLED ? now() : null,
                'cancelled_by' => $data['status'] === StudentApografiko::STATUS_CANCELLED ? $request->user()->id : null,
                'cancellation_reason' => $data['status'] === StudentApografiko::STATUS_CANCELLED ? ($data['cancellation_reason'] ?? null) : null,
            ])->save();

            if ($data['status'] === StudentApografiko::STATUS_NOT_REQUIRED) {
                $documentType = DocumentType::query()->where('code', $type)->firstOrFail();
                if (! $student->hasCompletedDocument($type)) {
                    $documents->markNotRequired($student, $documentType, $request->user(), 'Δεν απαιτείται απογραφικό δελτίο για αυτή τη φάση.');
                }
            }

            if ($data['status'] === StudentApografiko::STATUS_DRAFT) {
                $documentType = DocumentType::query()->where('code', $type)->firstOrFail();
                $student->documents()
                    ->where('document_type_id', $documentType->id)
                    ->where('status', 'not_required')
                    ->whereNull('path')
                    ->latest('version')
                    ->first()?->delete();
            }
        });

        $message = match ($data['status']) {
            StudentApografiko::STATUS_CANCELLED => 'Το απογραφικό καταχωρίστηκε ως ακυρωμένο στο ΟΠΣ.',
            StudentApografiko::STATUS_COMPLETED => 'Η καταχώριση ακύρωσης του απογραφικού αναιρέθηκε.',
            StudentApografiko::STATUS_NOT_REQUIRED => 'Καταχωρίστηκε ότι το απογραφικό δεν απαιτείται.',
            StudentApografiko::STATUS_DRAFT => 'Το απογραφικό απαιτείται ξανά και μπορεί να συμπληρωθεί.',
        };

        return redirect()->route('students.show', ['student' => $student, 'panel' => 'questionnaires'])
            ->with('status', $message);
    }

    private function authorizeRequest(Student $student, string $type): void
    {
        abort_unless(in_array($type, ['input', 'output'], true), 404);
        abort_unless(Gate::allows('view', $student), 404);
        abort_unless(Gate::allows('update', $student), 403);
    }
}
