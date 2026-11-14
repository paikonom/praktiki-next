<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CompletionCertificateController extends Controller
{
    public function providerPortal(Request $request): Response
    {
        $student = $request->user('provider');

        return Pdf::loadView('provider.completion-certificate-pdf', $this->providerData($student))
            ->setPaper('a4')
            ->stream("vevaiwsh_Forea_{$student->id}.pdf");
    }

    public function provider(Student $student): Response
    {
        abort_unless(Gate::allows('view', $student), 404);

        return Pdf::loadView('provider.completion-certificate-pdf', $this->providerData($student))
            ->setPaper('a4')
            ->stream("vevaiwsh_Forea_{$student->id}.pdf");
    }

    public function supervisor(Request $request, Student $student): Response
    {
        if ($request->routeIs('scientific_supervisor.*')) {
            abort_unless($student->supervisor_id === $request->user('scientific_supervisor')->id, 404);
        } else {
            abort_unless(Gate::allows('view', $student), 404);
        }

        return Pdf::loadView('scientific-supervisor.completion-certificate-pdf', $this->supervisorData($student))
            ->setPaper('a4')
            ->stream("vevaiwsh_EY_{$student->id}.pdf");
    }

    /** @return array<string, mixed> */
    private function providerData(Student $student): array
    {
        $student->loadMissing(['department', 'provider', 'phase.praktiki.funding']);

        return [
            'student' => $student,
            'provider' => $student->provider,
            'department' => $student->department,
            'funding' => $student->fundingCode(),
        ];
    }

    /** @return array<string, mixed> */
    private function supervisorData(Student $student): array
    {
        $student->loadMissing(['department', 'provider', 'supervisor', 'phase.praktiki.funding']);
        abort_unless($student->fundingCode() === 'Espa', 404);

        return [
            'student' => $student,
            'provider' => $student->provider,
            'supervisor' => $student->supervisor,
            'department' => $student->department,
            'details' => $student->phase?->praktiki?->details ?? [],
        ];
    }
}
