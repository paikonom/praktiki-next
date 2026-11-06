<?php

namespace App\Http\Controllers;

use App\Mail\WorkflowEmail;
use App\Models\EmailLog;
use App\Models\Student;
use App\Services\StudentEmailService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudentEmailController extends Controller
{
    public function preview(Request $request, Student $student, string $type, StudentEmailService $emails): View
    {
        Gate::authorize('update', $student);
        $this->loadStudent($student);
        $email = $emails->prepare($student, $type);

        return view('students.email-preview', [
            'student' => $student,
            'email' => $email,
            'body' => (new WorkflowEmail($email['subject'], $email['data']))->render(),
        ]);
    }

    public function store(Request $request, Student $student, string $type, StudentEmailService $emails): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $student);
        $this->loadStudent($student);

        if ($reason = $emails->unavailableReason($student, $type)) {
            throw ValidationException::withMessages(['email' => $reason]);
        }

        $email = $emails->prepare($student, $type);
        $mailable = new WorkflowEmail($email['subject'], $email['data']);
        $body = $mailable->render();
        $log = EmailLog::create([
            'student_id' => $student->id,
            'sent_by_user_id' => $request->user()->id,
            'type' => $type,
            'to' => $email['to'],
            'cc' => $email['cc'],
            'subject' => $email['subject'],
            'body' => $body,
            'status' => 'pending',
        ]);

        try {
            Mail::to($email['to'])->cc($email['cc'])->send($mailable);
            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            report($exception);
            $log->update(['status' => 'failed', 'error_message' => $exception->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Η αποστολή απέτυχε και καταγράφηκε στο ιστορικό.'], 500);
            }

            return back()->with('status_type', 'danger')->with('status', 'Η αποστολή απέτυχε και καταγράφηκε στο ιστορικό.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Το email στάληκε επιτυχώς.',
                'count' => $student->emailLogs()->where('type', $type)->where('status', 'sent')->count(),
            ]);
        }

        return redirect()->route('students.show', ['student' => $student, 'panel' => 'emails'])
            ->with('status', 'Το email στάλθηκε επιτυχώς.');
    }

    private function loadStudent(Student $student): void
    {
        $student->loadMissing(['department', 'provider', 'supervisor', 'actions', 'phase.praktiki.funding', 'documents.type']);
    }
}
