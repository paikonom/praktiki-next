<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\WorkflowAction;
use App\Support\WorkflowDisplay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StudentWorkflowActionController extends Controller
{
    public function store(Request $request, Student $student, string $action, WorkflowDisplay $workflowDisplay): RedirectResponse
    {
        abort_unless(Gate::allows('update', $student), 404);
        abort_unless(in_array($action, config('praktiki_workflow.member_manual_actions'), true), 404);
        $workflowAction = WorkflowAction::activeForFunding($student->fundingCode())->firstWhere('code', $action);
        abort_unless($workflowAction, 404);
        $data = $request->validate(['status' => ['required', Rule::in($workflowAction->allowed_statuses)]]);
        if (! $workflowDisplay->canPerform($student, $action, 'member', $data['status'])) {
            $message = $action === 'katastash_praktikis' && $data['status'] === 'completed'
                ? 'Για να ολοκληρωθεί η πρακτική πρέπει πρώτα να ολοκληρωθεί η κατάσταση αιτήματος πληρωμής.'
                : 'Η ενέργεια δεν επιτρέπεται ακόμη. Ελέγξτε τις προηγούμενες απαιτούμενες ενέργειες.';

            return back()->withErrors(['status' => $message]);
        }

        $student->actions()->updateOrCreate(['action' => $action], [
            'status' => $data['status'],
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'review_comment' => null,
        ]);

        return redirect()
            ->route('students.show', ['student' => $student, 'panel' => 'office'])
            ->with('status', 'Η κατάσταση ενημερώθηκε.');
    }
}
