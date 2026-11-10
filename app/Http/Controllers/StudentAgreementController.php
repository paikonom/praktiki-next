<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class StudentAgreementController extends Controller
{
    public function preview(Student $student): Response
    {
        abort_unless(Gate::allows('view', $student), 404);
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
            ->setPaper('a4')
            ->stream("symf_initial_{$student->id}.pdf");
    }
}
