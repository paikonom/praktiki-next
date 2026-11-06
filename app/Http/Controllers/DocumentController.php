<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewStudentDocumentRequest;
use App\Http\Requests\StoreStudentDocumentRequest;
use App\Models\DocumentType;
use App\Models\ScientificSupervisor;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\StudentDocumentService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private StudentDocumentService $documentService) {}

    public function index(Request $request): View
    {
        $context = $this->documentContext($request);

        if ($context['role'] === 'member') {
            Gate::forUser($context['user'])->authorize('view', $context['student']);
        }

        $context['student']->loadMissing('phase.praktiki.funding');

        return view('documents.page', [
            'student' => $context['student'],
            'types' => DocumentType::visibleForStudentAndRole($context['student'], $context['role']),
            'role' => $context['role'],
        ]);
    }

    public function store(StoreStudentDocumentRequest $request): RedirectResponse
    {
        $context = $this->documentContext($request);
        $documentType = $request->route('documentType');

        if (! $documentType instanceof DocumentType) {
            $documentType = DocumentType::findOrFail($documentType);
        }

        Gate::forUser($context['user'])->authorize('upload', [StudentDocument::class, $documentType, $context['student'], $context['role']]);
        $this->documentService->store($context['student'], $documentType, $request->file('file'), $context['user'], $context['role']);

        return $this->returnToDocuments($request, $context['student'])
            ->with('status', 'Το αρχείο ανέβηκε επιτυχώς.');
    }

    public function preview(Request $request, StudentDocument $studentDocument): StreamedResponse
    {
        $context = $this->documentContext($request, $studentDocument);

        return $this->fileResponse($context['user'], $context['role'], $studentDocument, true);
    }

    public function download(Request $request, StudentDocument $studentDocument): StreamedResponse
    {
        $context = $this->documentContext($request, $studentDocument);

        // dd($context['user']->toArray(), $context['role'], $context['student']->toArray());
        return $this->fileResponse($context['user'], $context['role'], $studentDocument, false);
    }

    public function destroy(Request $request, StudentDocument $studentDocument): RedirectResponse
    {
        $context = $this->documentContext($request, $studentDocument);
        Gate::forUser($context['user'])->authorize('delete', [$studentDocument, $context['role']]);
        $this->documentService->delete($studentDocument);

        return $this->returnToDocuments($request, $context['student'])
            ->with('status', 'Το αρχείο διαγράφηκε οριστικά.');
    }

    public function markNotRequired(Request $request, Student $student, DocumentType $documentType): RedirectResponse
    {
        Gate::authorize('upload', [StudentDocument::class, $documentType, $student, 'member']);
        $validatedData = $request->validate(['review_notes' => ['required', 'string', 'max:2000']]);
        $this->documentService->markNotRequired($student, $documentType, $request->user(), $validatedData['review_notes']);

        return back()->with('status', 'Το έγγραφο σημειώθηκε ως μη απαιτούμενο.');
    }

    public function update(ReviewStudentDocumentRequest $request, StudentDocument $studentDocument): RedirectResponse
    {
        Gate::authorize('review', $studentDocument);
        $studentDocument->update([
            'status' => $request->validated('status'),
            'review_notes' => $request->validated('review_notes'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Ο έλεγχος αποθηκεύτηκε.');
    }

    /** @return array{user: Authenticatable, role: string, student: Student} */
    private function documentContext(Request $request, ?StudentDocument $studentDocument = null): array
    {
        if ($request->routeIs('student.*')) {
            return ['user' => $request->user('student'), 'role' => 'student', 'student' => $request->user('student')];
        }

        if ($request->routeIs('provider.*')) {
            return ['user' => $request->user('provider'), 'role' => 'provider', 'student' => $request->user('provider')];
        }

        $student = $studentDocument?->student ?? $request->route('student');

        if ($student !== null && ! $student instanceof Student) {
            $student = Student::findOrFail($student);
        }

        abort_unless($student instanceof Student, 404);

        if ($request->routeIs('scientific_supervisor.*')) {
            /** @var ScientificSupervisor $supervisor */
            $supervisor = $request->user('scientific_supervisor');
            abort_unless($student->supervisor_id === $supervisor->id, 404);

            return ['user' => $supervisor, 'role' => 'scientific_supervisor', 'student' => $student];
        }

        return ['user' => $request->user(), 'role' => 'member', 'student' => $student];
    }

    private function fileResponse(Authenticatable $user, string $role, StudentDocument $studentDocument, bool $showInBrowser): StreamedResponse
    {
        Gate::forUser($user)->authorize('view', [$studentDocument, $role]);

        if ($showInBrowser) {
            return Storage::disk($studentDocument->disk)->response($studentDocument->path, $studentDocument->original_name, ['Content-Type' => 'application/pdf']);
        }

        return Storage::disk($studentDocument->disk)->download($studentDocument->path, $studentDocument->original_name);
    }

    private function returnToDocuments(Request $request, Student $student): RedirectResponse
    {
        if ($request->routeIs('scientific_supervisor.*')) {
            return redirect()->route('scientific_supervisor.students.show', ['student' => $student, 'panel' => 'documents']);
        }

        return back();
    }
}
