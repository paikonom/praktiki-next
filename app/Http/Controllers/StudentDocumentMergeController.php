<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentDocumentMergeService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentDocumentMergeController extends Controller
{
    public function show(Student $student, StudentDocumentMergeService $merger): View
    {
        Gate::authorize('view', $student);

        return view('documents.merge', [
            'student' => $student,
            'files' => $merger->inspect($student),
        ]);
    }

    public function download(Student $student, StudentDocumentMergeService $merger): BinaryFileResponse
    {
        Gate::authorize('view', $student);

        return response()
            ->download($merger->merge($student), "student-{$student->id}-documents.pdf", ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend();
    }
}
