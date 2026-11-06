<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveWorkflowSectionRuleRequest;
use App\Models\WorkflowSectionRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkflowSectionRuleController extends Controller
{
    public function index(Request $request): View
    {
        $rules = WorkflowSectionRule::query()
            ->when($request->filled('section'), fn ($query) => $query->where('section', $request->string('section')))
            ->when($request->filled('funding_code'), fn ($query) => $query->where('funding_code', $request->string('funding_code')))
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->orderByRaw("FIELD(section, 'A', 'B', 'G', 'D')")
            ->orderBy('role')
            ->orderBy('funding_code')
            ->get();

        return view('admin.workflow-section-rules.index', [
            'rules' => $rules,
        ]);
    }

    public function create(): View
    {
        return view('admin.workflow-section-rules.form', [
            'workflowSectionRule' => new WorkflowSectionRule,
        ]);
    }

    public function store(SaveWorkflowSectionRuleRequest $request): RedirectResponse
    {
        WorkflowSectionRule::create($this->data($request));

        return redirect()->route('admin.workflow-section-rules.index')->with('status', 'Ο κανόνας δημιουργήθηκε.');
    }

    public function edit(WorkflowSectionRule $workflowSectionRule): View
    {
        return view('admin.workflow-section-rules.form', [
            'workflowSectionRule' => $workflowSectionRule,
        ]);
    }

    public function update(SaveWorkflowSectionRuleRequest $request, WorkflowSectionRule $workflowSectionRule): RedirectResponse
    {
        $workflowSectionRule->update($this->data($request));

        return redirect()->route('admin.workflow-section-rules.index')->with('status', 'Ο κανόνας ενημερώθηκε.');
    }

    public function destroy(WorkflowSectionRule $workflowSectionRule): RedirectResponse
    {
        $workflowSectionRule->delete();

        return redirect()->route('admin.workflow-section-rules.index')->with('status', 'Ο κανόνας διαγράφηκε.');
    }

    /** @return array<string, mixed> */
    private function data(SaveWorkflowSectionRuleRequest $request): array
    {
        return $request->validated();
    }
}
