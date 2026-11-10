<?php

namespace App\Support;

use App\Models\Student;
use App\Models\WorkflowAction;
use App\Models\WorkflowSectionRule;
use Illuminate\Support\Collection;

class WorkflowDisplay
{
    /** @var Collection<int, WorkflowAction> */
    private Collection $currentActions;

    public function __construct(
        private StudentQuestionnaireDefinition $studentQuestionnaire,
        private ProviderQuestionnaireDefinition $providerQuestionnaire,
        private StudentApografikoDefinition $apografiko,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function sections(Student $student, string $currentRole): array
    {
        $student->loadMissing(['actions', 'documents.type', 'apografika', 'questionnaire', 'providerQuestionnaire']);
        $funding = $student->fundingCode();
        $actions = $this->actionsFor($funding);
        $this->currentActions = $actions;
        $rules = $this->rulesFor($funding, $currentRole);
        $rows = $actions->mapWithKeys(fn (WorkflowAction $action): array => [
            $action->code => $this->row($student, $action, $currentRole, $rules->get($action->section)),
        ]);
        $previousDone = true;

        return collect(['A', 'B', 'G', 'D'])->map(function (string $section) use ($student, $actions, $rows, $rules, &$previousDone): array {
            $rule = $rules->get($section);
            $availableByDate = $this->dateReached($rule?->available_after ?? 'always', $student);
            $previousSectionReady = ! $rule?->requires_previous_section || $previousDone;
            $sectionActions = $actions->where('section', $section)->where('counts_for_section', true);
            $done = $availableByDate
                && $previousSectionReady
                && $sectionActions->every(fn (WorkflowAction $action): bool => $rows->get($action->code)['done'] ?? false);
            $sectionRows = $actions->where('section', $section)->map(function (WorkflowAction $action) use ($section, $rows, $previousSectionReady): array {
                $row = $rows->get($action->code);
                $canChangeImmediately = $action->code === 'katastash_praktikis';
                if (! $previousSectionReady && ! $canChangeImmediately && ! $row['done'] && $row['responsible'] === $row['currentRole']) {
                    $previousSection = ['B' => 'Α', 'G' => 'Β', 'D' => 'Γ'][$section] ?? '';
                    $row['blockedMessage'] = "Πρέπει πρώτα να ολοκληρωθεί η ενότητα {$previousSection}.";
                }

                return $row;
            })->values();
            $previousDone = $done;

            return [
                'code' => $section,
                'title' => match ($section) {
                    'A' => 'Α. Πριν την έναρξη',
                    'B' => 'Β. Κατά τη διάρκεια',
                    'G' => 'Γ. Μετά τη λήξη',
                    'D' => 'Δ. Ολοκλήρωση Πρακτικής Άσκησης',
                },
                'done' => $done,
                'status' => $done ? 'completed' : 'pending',
                'rows' => $sectionRows->values()->all(),
            ];
        })->all();
    }

    public function sectionIsComplete(Student $student, string $section, string $role = 'member'): bool
    {
        $student->loadMissing(['actions', 'documents.type', 'apografika', 'questionnaire', 'providerQuestionnaire']);
        $actions = $this->actionsFor($student->fundingCode());
        $rules = $this->rulesFor($student->fundingCode(), $role);
        $previousDone = true;

        foreach (['A', 'B', 'G', 'D'] as $sectionCode) {
            $rule = $rules->get($sectionCode);
            $done = $this->dateReached($rule?->available_after ?? 'always', $student)
                && (! $rule?->requires_previous_section || $previousDone)
                && $actions->where('section', $sectionCode)->where('counts_for_section', true)
                    ->every(fn (WorkflowAction $action): bool => $action->isCompleteFor($student));

            if ($sectionCode === $section) {
                return $done;
            }

            $previousDone = $done;
        }

        return false;
    }

    public function canPerform(Student $student, string $code, string $role, ?string $newStatus = null): bool
    {
        $student->loadMissing(['actions', 'documents.type', 'apografika', 'questionnaire', 'providerQuestionnaire']);
        $this->currentActions = $this->actionsFor($student->fundingCode());
        $action = $this->currentActions->firstWhere('code', $code);
        $sectionRule = $action
            ? WorkflowSectionRule::forContext($student->fundingCode(), $action->section, $role)
            : null;
        $dateReached = $this->dateReached($sectionRule?->available_after ?? 'always', $student);

        return $action
            && $action->responsible_role === $role
            && ($dateReached || $this->isImmediateInternshipStatus($action, $newStatus))
            && collect($action->prerequisites)->every(fn (string $prerequisite): bool => $this->actionComplete($student, $prerequisite))
            && ($this->isImmediateInternshipStatus($action, $newStatus) || $this->previousSectionIsComplete($student, $action))
            && $this->paymentRequestAllowsInternshipStatus($student, $action, $newStatus);
    }

    /** @return Collection<int, WorkflowAction> */
    private function actionsFor(string $funding): Collection
    {
        return WorkflowAction::activeForFunding($funding);
    }

    /** @return Collection<string, WorkflowSectionRule> */
    private function rulesFor(string $funding, string $role): Collection
    {
        return WorkflowSectionRule::query()
            ->where('role', $role)
            ->whereIn('funding_code', ['all', $funding])
            ->orderByRaw('funding_code = ? desc', [$funding])
            ->get()
            ->unique('section')
            ->keyBy('section');
    }

    /** @return array<string, mixed> */
    private function row(Student $student, WorkflowAction $action, string $currentRole, ?WorkflowSectionRule $sectionRule): array
    {
        $status = $action->statusFor($student);
        $done = $action->isCompleteFor($student);
        $availableAfter = $sectionRule?->available_after ?? 'always';
        $dateReached = $this->dateReached($availableAfter, $student);
        $prerequisitesDone = $action->prerequisitesAreCompleteFor($student, $this->currentActions);

        return [
            'code' => $action->code,
            'sourceType' => $action->source_type,
            'sourceKey' => $action->source_key,
            'number' => $this->displayNumber($action),
            'title' => $action->title,
            'responsible' => $action->responsible_role,
            'currentRole' => $currentRole,
            'done' => $done,
            'status' => $status,
            'statusLabel' => StatusBadge::label($status),
            'statusClass' => StatusBadge::className($status),
            'blockedMessage' => $this->blockedMessage($student, $action, $availableAfter, $dateReached, $prerequisitesDone, $status),
        ];
    }

    private function actionComplete(Student $student, string $code): bool
    {
        $action = $this->currentActions->firstWhere('code', $code);

        return $action?->isCompleteFor($student) ?? false;
    }

    private function dateReached(string $availableAfter, Student $student): bool
    {
        return match ($availableAfter) {
            'internship_start' => $student->startDate && now()->gte($student->startDate),
            'internship_end' => $student->endDate && now()->gte($student->endDate),
            default => true,
        };
    }

    private function blockedMessage(Student $student, WorkflowAction $action, string $availableAfter, bool $dateReached, bool $prerequisitesDone, ?string $status): ?string
    {
        if ($action->source_type === 'student_questionnaire') {
            return $this->studentQuestionnaire->unavailableReason($student);
        }

        if ($action->source_type === 'provider_questionnaire') {
            return $this->providerQuestionnaire->unavailableReason($student);
        }

        if ($action->source_type === 'apografiko') {
            return $this->apografiko->unavailableReason($student, $action->source_key);
        }

        if (! $dateReached) {
            return $availableAfter === 'internship_start'
                ? 'Δεν έχει φτάσει ακόμη η ημερομηνία έναρξης της πρακτικής.'
                : 'Δεν έχει φτάσει ακόμη η ημερομηνία λήξης της πρακτικής.';
        }

        if (! $prerequisitesDone) {
            return 'Πρέπει πρώτα να ολοκληρωθούν οι προηγούμενες απαιτούμενες ενέργειες.';
        }

        if ($action->code === 'katastash_praktikis' && ! $this->actionComplete($student, 'aitima_plhrwmhs')) {
            return 'Για να ολοκληρωθεί η πρακτική πρέπει πρώτα να ολοκληρωθεί η κατάσταση αιτήματος πληρωμής.';
        }

        if ($status === 'submitted' && in_array($action->source_type, ['document', 'action'], true)) {
            return 'Αναμονή ελέγχου από το Γραφείο Πρακτικής Άσκησης.';
        }

        return null;
    }

    private function previousSectionIsComplete(Student $student, WorkflowAction $action): bool
    {
        $previousSection = ['B' => 'A', 'G' => 'B', 'D' => 'G'][$action->section] ?? null;
        if (! $previousSection) {
            return true;
        }

        $rule = $this->rulesFor($student->fundingCode(), $action->responsible_role)->get($action->section);

        return ! $rule?->requires_previous_section || $this->sectionIsComplete($student, $previousSection, $action->responsible_role);
    }

    private function isImmediateInternshipStatus(WorkflowAction $action, ?string $newStatus): bool
    {
        return $action->code === 'katastash_praktikis'
            && in_array($newStatus, ['active', 'cancelled'], true);
    }

    private function paymentRequestAllowsInternshipStatus(Student $student, WorkflowAction $action, ?string $newStatus): bool
    {
        if ($action->code !== 'katastash_praktikis' || $newStatus !== 'completed') {
            return true;
        }

        return $this->actionComplete($student, 'aitima_plhrwmhs');
    }

    private function displayNumber(WorkflowAction $action): string
    {
        $section = ['A' => 'Α', 'B' => 'Β', 'G' => 'Γ', 'D' => 'Δ'][$action->section];
        $position = $this->currentActions
            ->where('section', $action->section)
            ->values()
            ->search(fn (WorkflowAction $candidate): bool => $candidate->id === $action->id);

        return $section.($position + 1);
    }
}
