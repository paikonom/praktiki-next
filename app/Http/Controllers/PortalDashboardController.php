<?php

namespace App\Http\Controllers;

use App\Support\WorkflowDisplay;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PortalDashboardController extends Controller
{
    public function __invoke(Request $request, WorkflowDisplay $workflowDisplay): View
    {
        if ($request->routeIs('student.*')) {
            $student = $request->user('student');
            $role = 'student';
            $student->load([
                'department', 'phase.praktiki.funding', 'provider', 'actions',
                'questionnaire', 'documents.type', 'apografika',
            ]);
        } else {
            $student = $request->user('provider');
            $role = 'provider';
            $student->load([
                'department', 'phase.praktiki.funding', 'provider', 'actions',
                'providerQuestionnaire', 'documents.type',
            ]);
        }

        return view('shared.portal-dashboard', [
            'student' => $student,
            'role' => $role,
            'workflowSections' => $workflowDisplay->sections($student, $role),
        ]);
    }
}
