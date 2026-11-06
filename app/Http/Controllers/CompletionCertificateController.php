<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\CompletionCertificateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CompletionCertificateController extends Controller
{
    public function providerPortal(Request $request, CompletionCertificateService $certificates): Response
    {
        $student = $request->user('provider');

        return Pdf::loadView('provider.completion-certificate-pdf', $certificates->providerData($student))
            ->setPaper('a4')
            ->stream("vevaiwsh_Forea_{$student->id}.pdf");
    }

    public function provider(Student $student, CompletionCertificateService $certificates): Response
    {
        abort_unless(Gate::allows('view', $student), 404);

        return Pdf::loadView('provider.completion-certificate-pdf', $certificates->providerData($student))
            ->setPaper('a4')
            ->stream("vevaiwsh_Forea_{$student->id}.pdf");
    }

    public function supervisor(Request $request, Student $student, CompletionCertificateService $certificates): Response
    {
        if ($request->routeIs('scientific_supervisor.*')) {
            abort_unless($student->supervisor_id === $request->user('scientific_supervisor')->id, 404);
        } else {
            abort_unless(Gate::allows('view', $student), 404);
        }

        return Pdf::loadView('scientific-supervisor.completion-certificate-pdf', $certificates->supervisorData($student))
            ->setPaper('a4')
            ->stream("vevaiwsh_EY_{$student->id}.pdf");
    }
}
