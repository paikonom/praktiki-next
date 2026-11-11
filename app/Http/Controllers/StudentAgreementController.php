<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Services\StudentDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class StudentAgreementController extends Controller
{
    public function preview(Student $student): Response
    {
        abort_unless(Gate::allows('view', $student), 404);

        return $this->agreementPdf($student)
            ->stream("symf_initial_{$student->id}.pdf");
    }

    public function store(Student $student, StudentDocumentService $documents): RedirectResponse
    {
        $documentType = DocumentType::where('code', 'symf_initial')->firstOrFail();
        Gate::authorize('upload', [StudentDocument::class, $documentType, $student, 'member']);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'symf_initial_');
        if ($temporaryPath === false || file_put_contents($temporaryPath, $this->agreementPdf($student)->output()) === false) {
            throw new RuntimeException('Το συμφωνητικό δεν δημιουργήθηκε.');
        }

        try {
            $file = new UploadedFile($temporaryPath, "symf_initial_{$student->id}.pdf", 'application/pdf', null, true);
            $documents->store($student, $documentType, $file, request()->user(), 'member');
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        return redirect()->route('students.show', ['student' => $student, 'panel' => 'agreement'])
            ->with('status', 'Το αρχικό συμφωνητικό αποθηκεύτηκε και εγκρίθηκε.');
    }

    private function agreementPdf(Student $student)
    {
        $student->loadMissing(['department', 'provider', 'supervisor', 'phase.praktiki.funding']);

        $funding = $student->fundingCode();
        abort_unless(in_array($funding, ['Espa', 'Institution', 'InternshipProvider'], true), 404);

        $view = 'students.agreements.'.match ($funding) {
            'Espa' => 'espa',
            'Institution' => 'institution',
            'InternshipProvider' => 'internship-provider',
        };

        return Pdf::loadView($view, [
            'student' => $student,
            'provider' => $student->provider,
            'department' => $student->department,
            'phase' => $student->phase,
            'praktiki' => $student->phase?->praktiki,
            'supervisor' => $student->supervisor,
        ])
            ->setPaper('a4');
    }
}
