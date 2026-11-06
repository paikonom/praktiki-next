<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Student;
use App\Support\RescomReferenceData;
use App\Support\StudentWorkflow;
use App\Support\VisibleInternshipDetails;
use App\Support\WorkflowDisplay;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupervisorPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $supervisor = $request->user('scientific_supervisor');
        $years = $supervisor->students()
            ->join('praktiki_phases', 'praktiki_phases.id', '=', 'students.fash_id')
            ->join('praktiki_cycles', 'praktiki_cycles.id', '=', 'praktiki_phases.praktiki_cycle_id')
            ->distinct()->orderByDesc('praktiki_cycles.praxh_year')->pluck('praktiki_cycles.praxh_year');
        $fundings = DB::table('fundings')
            ->join('praktiki_cycles', 'praktiki_cycles.funding_id', '=', 'fundings.id')
            ->join('praktiki_phases', 'praktiki_phases.praktiki_cycle_id', '=', 'praktiki_cycles.id')
            ->join('students', 'students.fash_id', '=', 'praktiki_phases.id')
            ->where('students.supervisor_id', $supervisor->id)
            ->select('fundings.code', 'fundings.name', 'fundings.sort_order')
            ->distinct()->orderBy('fundings.sort_order')->get();
        $year = $request->has('year') ? $request->string('year')->toString() : '2025-2026';
        $year = $year === 'all' || $years->contains($year) ? $year : 'all';
        $funding = $request->has('funding') ? $request->string('funding')->toString() : 'Espa';
        $funding = $funding === 'all' || $fundings->pluck('code')->contains($funding) ? $funding : 'all';
        $phaseNames = $supervisor->students()
            ->join('praktiki_phases', 'praktiki_phases.id', '=', 'students.fash_id')
            ->join('praktiki_cycles', 'praktiki_cycles.id', '=', 'praktiki_phases.praktiki_cycle_id')
            ->join('fundings', 'fundings.id', '=', 'praktiki_cycles.funding_id')
            ->when($year !== 'all', fn ($query) => $query->where('praktiki_cycles.praxh_year', $year))
            ->when($funding !== 'all', fn ($query) => $query->where('fundings.code', $funding))
            ->whereNotNull('praktiki_phases.name')->distinct()->orderBy('praktiki_phases.name')
            ->pluck('praktiki_phases.name');
        $phase = $phaseNames->contains($request->string('phase')->toString()) ? $request->string('phase')->toString() : '';
        $status = in_array($request->string('status')->toString(), ['legacy', 'before', 'during', 'after', 'completed', 'canceled'], true)
            ? $request->string('status')->toString() : '';
        $search = trim($request->string('search')->toString());
        $students = $supervisor->students()
            ->with(['phase.praktiki.funding', 'actions'])
            ->when($year !== 'all', fn ($query) => $query->whereHas('phase.praktiki', fn ($query) => $query->where('praxh_year', $year)))
            ->when($funding !== 'all', fn ($query) => $query->whereHas('phase.praktiki.funding', fn ($query) => $query->where('code', $funding)))
            ->when($phase !== '', fn ($query) => $query->whereHas('phase', fn ($query) => $query->where('name', $phase)))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('AMF', 'like', "%{$search}%")
                    ->orWhere('fName', 'like', "%{$search}%")
                    ->orWhere('lName', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->get();

        $states = $students->mapWithKeys(fn (Student $student): array => [
            $student->id => StudentWorkflow::currentState($student),
        ]);
        if ($status !== '') {
            $students = $students->filter(fn (Student $student): bool => $states[$student->id] === $status)->values();
        }

        return view('scientific-supervisor.dashboard', compact(
            'students', 'states', 'years', 'fundings', 'phaseNames', 'year', 'funding', 'phase', 'status', 'search',
        ));
    }

    public function showStudent(
        Request $request,
        Student $student,
        WorkflowDisplay $workflowDisplay,
        VisibleInternshipDetails $visibleInternshipDetails,
        RescomReferenceData $rescomReferenceData,
    ): View {
        $this->ensureStudentBelongsToSupervisor($request, $student);
        $student->load(['department', 'phase.praktiki.funding', 'provider', 'supervisor', 'actions', 'documents.type', 'apografika', 'questionnaire', 'providerQuestionnaire']);

        $phase = $student->phase;
        $panel = $request->string('panel')->toString() === 'documents' ? 'documents' : 'actions';
        $documentTypes = DocumentType::visibleForStudentAndRole($student, 'scientific_supervisor');

        return view('scientific-supervisor.student', [
            'student' => $student,
            'department' => $student->department,
            'phase' => $phase,
            'funding' => $phase?->praktiki?->funding,
            'currentState' => StudentWorkflow::currentState($student),
            'panel' => $panel,
            'documentTypes' => $documentTypes,
            'workflowSections' => $workflowDisplay->sections($student, 'scientific_supervisor'),
            'visibleInternshipDetails' => $visibleInternshipDetails->forRole($student, $rescomReferenceData, 'scientific_supervisor'),
        ]);
    }

    private function ensureStudentBelongsToSupervisor(Request $request, Student $student): void
    {
        abort_unless($student->supervisor_id === $request->user('scientific_supervisor')->id, 404);
    }
}
