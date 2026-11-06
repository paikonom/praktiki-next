<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveWorkflowActionRequest;
use App\Models\WorkflowAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WorkflowActionController extends Controller
{
    public function index(Request $request): View
    {
        $actions = WorkflowAction::query()
            ->when($request->filled('section'), fn ($query) => $query->where('section', $request->string('section')))
            ->when($request->filled('funding_code'), fn ($query) => $query->where('funding_code', $request->string('funding_code')))
            ->when($request->filled('responsible_role'), fn ($query) => $query->where('responsible_role', $request->string('responsible_role')))
            ->when($request->filled('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search').'%';
                $query->where(fn ($query) => $query->where('title', 'like', $search)->orWhere('code', 'like', $search));
            })
            ->orderByRaw("FIELD(section, 'A', 'B', 'G', 'D')")
            ->orderBy('sort_order')
            ->orderBy('funding_code')
            ->get();

        return view('admin.workflow-actions.index', [
            'actions' => $actions,
        ]);
    }

    public function create(): View
    {
        return view('admin.workflow-actions.form', [
            'workflowAction' => new WorkflowAction,
            'prerequisiteActions' => $this->prerequisiteActions(),
        ]);
    }

    public function store(SaveWorkflowActionRequest $request): RedirectResponse
    {
        WorkflowAction::create($this->data($request));

        return redirect()->route('admin.workflow-actions.index')->with('status', 'Η ενέργεια δημιουργήθηκε.');
    }

    public function edit(WorkflowAction $workflowAction): View
    {
        return view('admin.workflow-actions.form', [
            'workflowAction' => $workflowAction,
            'prerequisiteActions' => $this->prerequisiteActions($workflowAction->code),
        ]);
    }

    public function update(SaveWorkflowActionRequest $request, WorkflowAction $workflowAction): RedirectResponse
    {
        $workflowAction->update($this->data($request));

        return redirect()->route('admin.workflow-actions.index')->with('status', 'Η ενέργεια ενημερώθηκε.');
    }

    public function destroy(WorkflowAction $workflowAction): RedirectResponse
    {
        $workflowAction->delete();

        return redirect()->route('admin.workflow-actions.index')->with('status', 'Η ενέργεια διαγράφηκε.');
    }

    /** @return array<string, mixed> */
    private function data(SaveWorkflowActionRequest $request): array
    {
        $data = $request->validated();
        $data['prerequisites'] = array_values($data['prerequisites'] ?? []);
        $data['allowed_statuses'] = array_values($data['allowed_statuses']);
        $data['completed_statuses'] = array_values($data['completed_statuses'] ?? []);

        return $data;
    }

    /** @return Collection<int, WorkflowAction> */
    private function prerequisiteActions(?string $exceptCode = null): Collection
    {
        return WorkflowAction::query()
            ->when($exceptCode, fn ($query) => $query->where('code', '!=', $exceptCode))
            ->orderByRaw("FIELD(section, 'A', 'B', 'G', 'D')")
            ->orderBy('sort_order')
            ->get()
            ->unique('code')
            ->values();
    }
}
