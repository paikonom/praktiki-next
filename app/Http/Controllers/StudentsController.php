<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStudentSectionRequest;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Student;
use App\Models\WorkflowAction;
use App\Services\StudentEmailService;
use App\Support\RescomReferenceData;
use App\Support\StudentCardMetadata;
use App\Support\StudentWorkflow;
use App\Support\WorkflowDisplay;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StudentsController extends Controller
{
    private const PANELS = ['office', 'agreement', 'student', 'provider', 'questionnaires', 'documents', 'emails'];

    public function index(Request $request, StudentEmailService $emails): View
    {
        $request->session()->put('students.index_url', $request->getRequestUri());

        $departments = $this->availableDepartments($request);
        $departmentIds = $departments->pluck('id');
        $years = $this->availableYears($departmentIds);

        $year = $request->string('year')->toString();
        $year = $year === 'all' || $years->contains($year) ? $year : ($years->first() ?? '');

        $fundings = $this->availableFundings($departmentIds);

        $funding = $request->string('funding')->toString();
        $defaultFunding = $this->defaultFunding($fundings, $departmentIds, $year);
        $funding = $funding === 'all' || $fundings->pluck('code')->contains($funding)
            ? $funding
            : $defaultFunding;
        $departmentId = $departments->pluck('id')->contains($request->integer('department_id'))
            ? $request->integer('department_id')
            : null;

        $phaseNames = $this->availablePhaseNames($departmentIds, $year, $funding, $departmentId);

        $phase = $request->string('phase')->toString();
        $phase = $phaseNames->contains($phase) ? $phase : '';
        $search = trim($request->string('search')->toString());

        $documentTypes = DocumentType::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['code', 'title']);
        $emailTypes = $emails->typeOptions();
        $filters = [
            'student_status' => $this->selectedFilter($request, 'student_status', ['active', 'completed', 'cancelled']),
            'date_stage' => $this->selectedFilter($request, 'date_stage', ['before_start', 'in_progress', 'after_end']),
            'weekly_hours' => $this->selectedFilter($request, 'weekly_hours', ['20', '40']),
            'details_status' => $this->selectedFilter($request, 'details_status', ['completed', 'pending']),
            'input_status' => $this->selectedFilter($request, 'input_status', ['completed', 'pending', 'not_required']),
            'output_status' => $this->selectedFilter($request, 'output_status', ['completed', 'pending', 'not_required']),
            'questionnaires_status' => $this->selectedFilter($request, 'questionnaires_status', ['completed', 'student_pending', 'provider_pending', 'pending']),
            'document_type' => $this->selectedFilter($request, 'document_type', $documentTypes->pluck('code')->all()),
            'document_status' => $this->selectedFilter($request, 'document_status', ['missing', 'submitted', 'approved', 'rejected', 'not_required']),
            'email_type' => $this->selectedFilter($request, 'email_type', array_keys($emailTypes)),
            'email_status' => $this->selectedFilter($request, 'email_status', ['sent', 'not_sent']),
        ];
        $hasAdvancedFilters = collect($filters)->contains(fn (string $value): bool => $value !== '');

        $students = $this->filteredStudents(
            $departmentIds,
            $year,
            $funding,
            $departmentId,
            $phase,
            $search,
            $filters,
        );
        $nextActions = $this->nextActions($students, $emails, $request);
        $studentGroups = $students->groupBy('department_id');
        $studentCount = $students->count();

        return view('students.index', compact(
            'studentGroups', 'studentCount', 'departments', 'years', 'fundings', 'phaseNames',
            'year', 'funding', 'departmentId', 'phase', 'search', 'nextActions',
            'documentTypes', 'emailTypes', 'filters', 'hasAdvancedFilters',
        ));
    }

    /** @param array<int, string> $allowed */
    private function selectedFilter(Request $request, string $name, array $allowed): string
    {
        $value = $request->string($name)->toString();

        return in_array($value, $allowed, true) ? $value : '';
    }

    private function availableDepartments(Request $request): Collection
    {
        $query = $request->user()->role === 'admin'
            ? Department::query()
            : $request->user()->departments();

        return $query
            ->select('departments.id', 'departments.atlasId', 'departments.tmhma')
            ->orderBy('departments.tmhma')
            ->get();
    }

    private function availableYears(Collection $departmentIds): Collection
    {
        return DB::table('students')
            ->join('praktiki_phases', 'praktiki_phases.id', '=', 'students.fash_id')
            ->join('praktiki_cycles', 'praktiki_cycles.id', '=', 'praktiki_phases.praktiki_cycle_id')
            ->whereIn('students.department_id', $departmentIds)
            ->distinct()
            ->orderByDesc('praktiki_cycles.praxh_year')
            ->pluck('praktiki_cycles.praxh_year');
    }

    private function availableFundings(Collection $departmentIds): Collection
    {
        return DB::table('fundings')
            ->join('praktiki_cycles', 'praktiki_cycles.funding_id', '=', 'fundings.id')
            ->join('praktiki_phases', 'praktiki_phases.praktiki_cycle_id', '=', 'praktiki_cycles.id')
            ->join('students', 'students.fash_id', '=', 'praktiki_phases.id')
            ->whereIn('students.department_id', $departmentIds)
            ->select('fundings.code', 'fundings.name', 'fundings.sort_order')
            ->distinct()
            ->orderBy('fundings.sort_order')
            ->get();
    }

    private function defaultFunding(Collection $fundings, Collection $departmentIds, string $year): string
    {
        $funding = $fundings->first(fn (object $availableFunding): bool => DB::table('students')
            ->join('praktiki_phases', 'praktiki_phases.id', '=', 'students.fash_id')
            ->join('praktiki_cycles', 'praktiki_cycles.id', '=', 'praktiki_phases.praktiki_cycle_id')
            ->join('fundings', 'fundings.id', '=', 'praktiki_cycles.funding_id')
            ->whereIn('students.department_id', $departmentIds)
            ->when($year !== 'all', fn ($query) => $query->where('praktiki_cycles.praxh_year', $year))
            ->where('fundings.code', $availableFunding->code)
            ->exists());

        return $funding->code ?? '';
    }

    private function availablePhaseNames(
        Collection $departmentIds,
        string $year,
        string $funding,
        ?int $departmentId,
    ): Collection {
        return DB::table('praktiki_phases')
            ->join('praktiki_cycles', 'praktiki_cycles.id', '=', 'praktiki_phases.praktiki_cycle_id')
            ->join('fundings', 'fundings.id', '=', 'praktiki_cycles.funding_id')
            ->whereIn('praktiki_phases.department_id', $departmentIds)
            ->when($year !== 'all', fn ($query) => $query->where('praktiki_cycles.praxh_year', $year))
            ->when($funding !== 'all', fn ($query) => $query->where('fundings.code', $funding))
            ->when($departmentId, fn ($query) => $query->where('praktiki_phases.department_id', $departmentId))
            ->whereNotNull('praktiki_phases.name')
            ->distinct()
            ->orderBy('praktiki_phases.name')
            ->pluck('praktiki_phases.name');
    }

    private function filteredStudents(
        Collection $departmentIds,
        string $year,
        string $funding,
        ?int $departmentId,
        string $phase,
        string $search,
        array $filters,
    ): Collection {
        $query = Student::query()
            ->join('departments', 'departments.id', '=', 'students.department_id')
            ->leftJoin('praktiki_phases', 'praktiki_phases.id', '=', 'students.fash_id')
            ->leftJoin('praktiki_cycles', 'praktiki_cycles.id', '=', 'praktiki_phases.praktiki_cycle_id')
            ->leftJoin('fundings', 'fundings.id', '=', 'praktiki_cycles.funding_id')
            ->whereIn('students.department_id', $departmentIds)
            ->when($year !== 'all', fn (Builder $query) => $query->where('praktiki_cycles.praxh_year', $year))
            ->when($funding !== 'all', fn (Builder $query) => $query->where('fundings.code', $funding))
            ->when($departmentId, fn (Builder $query) => $query->where('students.department_id', $departmentId))
            ->when($phase !== '', fn (Builder $query) => $query->where('praktiki_phases.name', $phase))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('students.AMF', 'like', "%{$search}%")
                    ->orWhere('students.fName', 'like', "%{$search}%")
                    ->orWhere('students.lName', 'like', "%{$search}%")
                    ->orWhere('students.afm', 'like', "%{$search}%")
                    ->orWhere('students.adt', 'like', "%{$search}%");
            }));

        $this->applyFilters($query, $filters);

        $students = $query
            ->select([
                'students.id', 'students.department_id', 'students.fName', 'students.lName', 'students.AMF',
                'students.startDate', 'students.endDate', 'students.diakoptomeno',
                'students.endDate_DKPT_A', 'students.startDate_DKPT_B',
                'departments.atlasId', 'departments.tmhma',
                'praktiki_phases.name as fashName', 'praktiki_cycles.praxh_year',
                'fundings.code as funding_code', 'fundings.name as funding_name',
            ])
            ->selectRaw("(SELECT status FROM student_actions WHERE student_id = students.id AND action = 'stoix_student') AS stoix_student_status")
            ->selectRaw("(SELECT status FROM student_actions WHERE student_id = students.id AND action = 'stoix_forea') AS stoix_forea_status")
            ->selectRaw("(SELECT sa.status FROM student_apografika sa WHERE sa.student_id = students.id AND sa.type = 'input' LIMIT 1) AS input_status")
            ->selectRaw("(SELECT sa.status FROM student_apografika sa WHERE sa.student_id = students.id AND sa.type = 'output' LIMIT 1) AS output_status")
            ->orderBy('departments.tmhma')
            ->orderBy('students.id')
            ->get();

        $this->preloadListStatuses($students);

        return $students;
    }

    /** @param array<string, string> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['student_status'] !== '') {
            $method = $filters['student_status'] === 'active' ? 'whereNotExists' : 'whereExists';
            $query->{$method}(fn ($status) => $status
                ->selectRaw('1')
                ->from('student_actions as internship_status')
                ->whereColumn('internship_status.student_id', 'students.id')
                ->where('internship_status.action', 'katastash_praktikis')
                ->whereIn(
                    'internship_status.status',
                    $filters['student_status'] === 'active' ? ['completed', 'cancelled'] : [$filters['student_status']],
                ));
        }

        if ($filters['date_stage'] === 'before_start') {
            $query->where(fn (Builder $dates) => $dates
                ->whereNull('students.startDate')
                ->orWhere('students.startDate', '>', today()));
        } elseif ($filters['date_stage'] === 'in_progress') {
            $query->where('students.startDate', '<=', today())
                ->where('students.endDate', '>=', today());
        } elseif ($filters['date_stage'] === 'after_end') {
            $query->where('students.endDate', '<', today());
        }

        if ($filters['weekly_hours'] !== '') {
            $query->where('students.wresPerWeek', (int) $filters['weekly_hours']);
        }

        if ($filters['details_status'] !== '') {
            $completed = function ($details, string $action): void {
                $details->selectRaw('1')
                    ->from('student_actions as detail_status')
                    ->whereColumn('detail_status.student_id', 'students.id')
                    ->where('detail_status.action', $action)
                    ->whereIn('detail_status.status', ['approved', 'not_required']);
            };

            if ($filters['details_status'] === 'completed') {
                foreach (['stoix_student', 'stoix_forea'] as $action) {
                    $query->whereExists(fn ($details) => $completed($details, $action));
                }
            } else {
                $query->where(fn (Builder $details) => $details
                    ->whereNotExists(fn ($action) => $completed($action, 'stoix_student'))
                    ->orWhereNotExists(fn ($action) => $completed($action, 'stoix_forea')));
            }
        }

        foreach (['input', 'output'] as $type) {
            $status = $filters[$type.'_status'];
            if ($status === '') {
                continue;
            }

            $method = $status === 'pending' ? 'whereNotExists' : 'whereExists';
            $query->{$method}(fn ($apografiko) => $apografiko
                ->selectRaw('1')
                ->from('student_apografika as filter_apografiko')
                ->whereColumn('filter_apografiko.student_id', 'students.id')
                ->where('filter_apografiko.type', $type)
                ->when(
                    $status === 'pending',
                    fn ($query) => $query->whereIn('filter_apografiko.status', ['completed', 'not_required']),
                    fn ($query) => $query->where('filter_apografiko.status', $status),
                ));
        }

        $this->applyQuestionnaireFilter($query, $filters['questionnaires_status']);
        $this->applyDocumentFilter($query, $filters['document_type'], $filters['document_status']);
        $this->applyEmailFilter($query, $filters['email_type'], $filters['email_status']);
    }

    private function applyQuestionnaireFilter(Builder $query, string $status): void
    {
        $studentCompleted = fn ($questionnaire) => $questionnaire
            ->selectRaw('1')
            ->from('student_questionnaires as filter_student_questionnaire')
            ->whereColumn('filter_student_questionnaire.s_id', 'students.id')
            ->where('filter_student_questionnaire.status', 'submitted');
        $providerCompleted = fn ($questionnaire) => $questionnaire
            ->selectRaw('1')
            ->from('provider_questionnaires as filter_provider_questionnaire')
            ->whereColumn('filter_provider_questionnaire.s_id', 'students.id')
            ->where('filter_provider_questionnaire.status', 'submitted');

        if ($status === 'completed') {
            $query->whereExists($studentCompleted)->whereExists($providerCompleted);
        } elseif ($status === 'student_pending') {
            $query->whereNotExists($studentCompleted);
        } elseif ($status === 'provider_pending') {
            $query->whereNotExists($providerCompleted);
        } elseif ($status === 'pending') {
            $query->where(fn (Builder $questionnaires) => $questionnaires
                ->whereNotExists($studentCompleted)
                ->orWhereNotExists($providerCompleted));
        }
    }

    private function applyDocumentFilter(Builder $query, string $type, string $status): void
    {
        if ($status === '' || ($status === 'missing' && $type === '')) {
            return;
        }

        $method = $status === 'missing' ? 'whereNotExists' : 'whereExists';
        $query->{$method}(function ($documents) use ($type, $status): void {
            $documents->selectRaw('1')
                ->from('student_documents as filter_document')
                ->join('document_types as filter_document_type', 'filter_document_type.id', '=', 'filter_document.document_type_id')
                ->whereColumn('filter_document.s_id', 'students.id')
                ->when($type !== '', fn ($query) => $query->where('filter_document_type.code', $type));

            if ($status !== 'missing') {
                $documents->where('filter_document.status', $status)
                    ->whereNotExists(fn ($newer) => $newer
                        ->selectRaw('1')
                        ->from('student_documents as newer_filter_document')
                        ->whereColumn('newer_filter_document.s_id', 'filter_document.s_id')
                        ->whereColumn('newer_filter_document.document_type_id', 'filter_document.document_type_id')
                        ->whereColumn('newer_filter_document.version', '>', 'filter_document.version'));
            }
        });
    }

    private function applyEmailFilter(Builder $query, string $type, string $status): void
    {
        if ($type === '' || $status === '') {
            return;
        }

        $method = $status === 'sent' ? 'whereExists' : 'whereNotExists';
        $query->{$method}(fn ($emails) => $emails
            ->selectRaw('1')
            ->from('email_logs as filter_email')
            ->whereColumn('filter_email.student_id', 'students.id')
            ->where('filter_email.type', $type)
            ->where('filter_email.status', 'sent'));
    }

    private function preloadListStatuses(Collection $students): void
    {
        $studentIds = $students->pluck('id');
        $statuses = array_fill_keys($studentIds->all(), []);
        $emailCounts = array_fill_keys($studentIds->all(), []);

        foreach (DB::table('student_actions')->whereIn('student_id', $studentIds)->cursor() as $row) {
            $statuses[$row->student_id]["action:{$row->action}"] = $row->status;
        }

        $latestDocuments = DB::table('student_documents as document')
            ->join('document_types as type', 'type.id', '=', 'document.document_type_id')
            ->whereIn('document.s_id', $studentIds)
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('student_documents as newer_document')
                ->whereColumn('newer_document.s_id', 'document.s_id')
                ->whereColumn('newer_document.document_type_id', 'document.document_type_id')
                ->whereColumn('newer_document.version', '>', 'document.version'))
            ->select('document.s_id as student_id', 'type.code', 'document.status');

        foreach ($latestDocuments->cursor() as $row) {
            $statuses[$row->student_id]["document:{$row->code}"] = $row->status;
        }

        foreach (DB::table('student_apografika')->whereIn('student_id', $studentIds)->cursor() as $row) {
            $statuses[$row->student_id]["apografiko:{$row->type}"] = $row->status;
        }

        foreach (DB::table('student_questionnaires')->whereIn('s_id', $studentIds)->cursor() as $row) {
            $statuses[$row->s_id]['student_questionnaire:student'] = $row->status;
        }

        foreach (DB::table('provider_questionnaires')->whereIn('s_id', $studentIds)->cursor() as $row) {
            $statuses[$row->s_id]['provider_questionnaire:provider'] = $row->status;
        }

        $sentEmails = DB::table('email_logs')
            ->whereIn('student_id', $studentIds)
            ->where('status', 'sent')
            ->select('student_id', 'type', DB::raw('COUNT(*) as total'))
            ->groupBy('student_id', 'type');

        foreach ($sentEmails->cursor() as $row) {
            $emailCounts[$row->student_id][$row->type] = (int) $row->total;
        }

        foreach ($students as $student) {
            $student->setPreloadedWorkflowStatuses($statuses[$student->id]);
            $student->setPreloadedSentEmailCounts($emailCounts[$student->id]);
        }
    }

    private function nextActions(Collection $students, StudentEmailService $emails, Request $request): Collection
    {
        $workflowActions = $students->map->fundingCode()->unique()->mapWithKeys(
            fn (string $fundingCode) => [$fundingCode => WorkflowAction::activeForFunding($fundingCode)],
        );

        return $students->mapWithKeys(function (Student $student) use ($workflowActions, $emails, $request): array {
            if ($student->isCancelled()) {
                return [$student->id => ['title' => 'Άκυρη', 'class' => 'text-bg-danger']];
            }

            if ($student->isLegacy()) {
                return [$student->id => ['title' => 'Legacy', 'class' => 'text-bg-secondary']];
            }

            $actions = $workflowActions->get($student->fundingCode(), collect());
            $nextSection = WorkflowAction::nextIncompleteSectionFor($student, $actions);

            if (! $nextSection) {
                return [$student->id => ['title' => 'Ολοκληρωμένη', 'class' => 'text-bg-success']];
            }

            $section = $nextSection['section'];
            $sectionLabel = ['A' => 'Α', 'B' => 'Β', 'G' => 'Γ', 'D' => 'Δ'][$section];
            $pendingActions = $nextSection['pendingActions'];
            $emailTypes = $emails->typesForPendingActions($section, $pendingActions);
            $emailEntries = $emailTypes === []
                ? collect()
                : $emails->entries($student)->whereIn('type', $emailTypes)->values();
            if ($request->user()->view_only) {
                $emailEntries = $emailEntries->map(function (array $email): array {
                    $email['unavailable_reason'] = 'Δεν έχετε δικαίωμα αποστολής.';

                    return $email;
                });
            }

            return [$student->id => [
                'title' => "Ενότητα {$sectionLabel} ({$pendingActions->count()})",
                'class' => 'text-bg-secondary',
                'code' => $pendingActions->first()?->code,
                'approval_count' => $pendingActions
                    ->where('source_type', 'document')
                    ->filter(fn (WorkflowAction $action): bool => $action->statusFor($student) === 'submitted')
                    ->count(),
                'emails' => $emailEntries,
            ]];
        });
    }

    public function show(
        Request $request,
        Student $student,
        RescomReferenceData $rescomReferenceData,
        StudentCardMetadata $metadata,
        StudentEmailService $emails,
        WorkflowDisplay $workflowDisplay,
    ): View {
        abort_unless(Gate::allows('view', $student), 404);

        $panel = $request->string('panel')->toString();
        $panel = in_array($panel, self::PANELS, true) ? $panel : 'office';

        $student->load(['department', 'phase.praktiki.funding', 'provider', 'actions', 'supervisor', 'questionnaire', 'providerQuestionnaire', 'apografika', 'documents.type', 'emailLogs.sentBy']);
        $department = $student->department;
        $phase = $student->phase;
        $praktiki = $phase?->praktiki;
        $funding = $praktiki?->funding;
        $provider = $student->provider;
        $fundingCode = $funding?->code ?? '';
        $studentMetadata = $metadata->fields('student', $fundingCode, 'member');
        if (! $student->diakoptomeno) {
            $studentMetadata = $studentMetadata->whereNotIn('field', ['endDate_DKPT_A', 'startDate_DKPT_B'])->values();
        }
        if (blank($student->address_odos_polh)) {
            $studentMetadata = $studentMetadata->where('field', '!=', 'address_odos_polh')->values();
        }
        $providerMetadata = $metadata->fields('provider', $fundingCode, 'member');
        $providerMetadata = $providerMetadata->reject(fn ($field): bool =>
            in_array($field->field, ['f_perioxh', 'f_topos_ekt_prakt'], true)
            && blank($provider?->{$field->field})
        )->values();
        $officeMetadata = $metadata->fields('office', $fundingCode, 'member');
        $studentFields = $metadata->labels($studentMetadata);
        $providerFields = $metadata->labels($providerMetadata);
        $officeFields = $metadata->labels($officeMetadata);
        $fieldGroups = [
            'student' => $metadata->groups($studentMetadata),
            'provider' => $metadata->groups($providerMetadata),
            'office' => $metadata->groups($officeMetadata),
        ];
        $fieldHelp = $metadata->help($studentMetadata)
            + $metadata->help($providerMetadata)
            + $metadata->help($officeMetadata);

        $currentState = StudentWorkflow::currentState($student);
        $canEdit = Gate::allows('update', $student);
        $fieldOptions = config('praktiki_fields.select_options');

        foreach (config('praktiki_fields.catalog_fields') as $field => $catalogName) {
            if ($catalogName !== 'cities') {
                $fieldOptions[$field] = $rescomReferenceData->options($catalogName);
            }
        }

        $cityValues = [
            'address_polh_rescom' => $request->old('address_polh_rescom', $student->address_polh_rescom),
            'f_polh_rescom' => $request->old('f_polh_rescom', $provider?->f_polh_rescom),
        ];
        $cityLabels = collect($cityValues)
            ->map(fn ($code) => $rescomReferenceData->label('cities', $code) ?? (filled($code) ? "Άγνωστος κωδικός: {$code}" : ''))
            ->all();

        $emailEntries = $panel === 'emails' ? $emails->entries($student) : collect();
        $agreementEmail = $panel === 'agreement'
            ? $emails->entries($student)->firstWhere('type', 'stoixeiaFoitEgkrish')
            : null;
        $officeWorkflowSections = $panel === 'office' ? $workflowDisplay->sections($student, 'member') : [];
        $documentTypes = $panel === 'documents'
            ? DocumentType::visibleForStudentAndRole($student, 'member')
            : collect();
        $handlerStatusActions = [];
        if ($panel === 'questionnaires') {
            foreach (['input', 'output'] as $type) {
                $handlerStatusActions['apografika'][$type] = [
                    'cancelled' => WorkflowAction::statusIsAllowedFor($student, 'apografiko', $type, 'cancelled'),
                    'not_required' => WorkflowAction::statusIsAllowedFor($student, 'apografiko', $type, 'not_required'),
                    'completed' => WorkflowAction::statusIsAllowedFor($student, 'apografiko', $type, 'completed'),
                    'draft' => WorkflowAction::statusIsAllowedFor($student, 'apografiko', $type, 'draft'),
                ];
            }
            foreach (['student', 'provider'] as $type) {
                $sourceType = $type === 'student' ? 'student_questionnaire' : 'provider_questionnaire';
                $handlerStatusActions['questionnaires'][$type] = [
                    'rejected' => WorkflowAction::statusIsAllowedFor($student, $sourceType, $type, 'rejected'),
                    'submitted' => WorkflowAction::statusIsAllowedFor($student, $sourceType, $type, 'submitted'),
                ];
            }
        }

        return view('students.show', compact(
            'student', 'panel', 'department', 'phase', 'praktiki', 'funding', 'provider',
            'studentFields', 'providerFields', 'officeFields',
            'fieldGroups', 'fieldHelp', 'currentState', 'canEdit', 'fieldOptions', 'cityLabels',
            'emailEntries', 'agreementEmail', 'officeWorkflowSections', 'documentTypes', 'handlerStatusActions',
        ));
    }

    public function updateSection(
        UpdateStudentSectionRequest $request,
        Student $student,
        string $section,
        StudentCardMetadata $metadata,
    ): RedirectResponse {
        $data = $request->validated();
        $changedFields = [];

        DB::transaction(function () use ($request, $student, $section, $data, &$changedFields): void {
            $actionFields = $section === 'office'
                ? array_keys($data)
                : [];
            foreach ($actionFields as $action) {
                $actionStatus = $data[$action];
                unset($data[$action]);

                if ($actionStatus === null) {
                    continue;
                }

                $existingAction = $student->actions()->where('action', $action)->first();

                if ($existingAction?->status === $actionStatus) {
                    continue;
                }

                $reviewed = in_array($actionStatus, ['approved', 'rejected', 'not_required'], true);
                $student->actions()->updateOrCreate(['action' => $action], [
                    'status' => $actionStatus,
                    'submitted_at' => $actionStatus === 'submitted' ? now() : $existingAction?->submitted_at,
                    'reviewed_at' => $reviewed ? now() : null,
                    'reviewed_by' => $reviewed ? $request->user()->id : null,
                ]);
                $changedFields[] = $action;
            }

            if ($data === []) {
                return;
            }

            $record = match ($section) {
                'student' => $student,
                'provider' => $student->provider()->firstOrNew(),
                default => $student,
            };

            $record->forceFill($data);
            $changedFields = [...$changedFields, ...array_keys(array_intersect_key($record->getDirty(), $data))];
            $record->save();
        });

        $student->loadMissing('phase.praktiki.funding');
        $fundingCode = $student->fundingCode();

        $labels = $metadata->labels($metadata->fields($section, $fundingCode, 'member'));

        $changedLabels = collect($changedFields)
            ->map(fn (string $field): string => $labels[$field] ?? $field)
            ->implode(', ');

        $status = $changedLabels === ''
            ? 'Δεν υπήρξε κάποια αλλαγή για αποθήκευση.'
            : 'Αποθηκεύτηκαν οι αλλαγές: '.$changedLabels.'.';

        return redirect()
            ->route('students.show', ['student' => $student, 'panel' => $section])
            ->with('status', $status)
            ->with('status_type', $changedLabels === '' ? 'info' : 'success');
    }
}
