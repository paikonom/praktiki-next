<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentAgreementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class StudentAgreementController extends Controller
{
    public function preview(Student $student, StudentAgreementService $agreements): Response
    {
        abort_unless(Gate::allows('view', $student), 404);
        $agreement = $agreements->build($student);

        return Pdf::loadView($agreement['view'], $agreement['data'])
            ->setPaper('a4')
            ->stream("symf_initial_{$student->id}.pdf");
    }
}
