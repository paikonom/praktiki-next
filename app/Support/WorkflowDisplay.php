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

    /** @var array<string, Collection<int, WorkflowAction>> */
    private array $actionsByFunding = [];

    /** @var array<string, Collection<string, WorkflowSectionRule>> */
    private array $rulesByContext = [];

    /** @return array<int, array<string, mixed>> */
    public function sections(Student $student, string $currentRole): array
    {
        $student->loadMissing(['actions', 'documents.type', 'apografika', 'questionnaire', 'providerQuestionnaire']);
        $funding = $student->fundingCode();
        $actions = $this->actionsFor($funding);
        $this->currentActions = $actions;
        $rules = $this->rulesFor($funding, $currentRole);
        $rows = $actions->mapWithKeys(fn (WorkflowAction $action): array => [
            $action->code => $this->row($student, $action, $currentRole),
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
        $canChangeImmediately = $action && $this->isImmediateInternshipStatus($action, $newStatus);

        return $action
            && $action->responsible_role === $role
            && ($canChangeImmediately || $this->unavailableReason($student, $code, $role) === null)
            && $this->paymentRequestAllowsInternshipStatus($student, $action, $newStatus);
    }

    public function unavailableReason(Student $student, string $code, string $role): ?string
    {
        $actions = $this->actionsFor($student->fundingCode());
        $action = $actions->firstWhere('code', $code);

        if (! $action || $action->responsible_role !== $role) {
            return in_array($code, ['input_sumpl', 'output_sumpl'], true)
                ? 'Απογραφικά δελτία απαιτούνται μόνο για πρακτική ΕΣΠΑ.'
                : 'Η ενέργεια δεν είναι διαθέσιμη για αυτή την πρακτική.';
        }

        $issue = $this->unavailableIssue($student, $action, $role);

        if ($issue === null) {
            return null;
        }

        if (str_starts_with($issue, 'previous_section:')) {
            $section = substr($issue, strlen('previous_section:'));

            return "Πρέπει πρώτα να ολοκληρωθούν όλες οι ενέργειες της ενότητας {$section}.";
        }

        return match ($issue) {
            'cancelled' => 'Η πρακτική έχει ακυρωθεί.',
            'missing_rule' => 'Δεν έχει οριστεί κανόνας διαθεσιμότητας για την ενέργεια.',
            'previous_step' => 'Πρέπει πρώτα να ολοκληρωθούν οι προηγούμενες απαιτούμενες ενέργειες.',
            default => $this->dateUnavailableReason($student, $action, $issue),
        };
    }

    public function unavailableIssue(
        Student $student,
        WorkflowAction $action,
        string $role,
        bool $ignorePreviousSections = false,
        bool $dateFirst = false,
    ): ?string {
        if ($student->isCancelled()) {
            return 'cancelled';
        }

        $actions = $this->actionsFor($student->fundingCode());
        $rule = $this->rulesFor($student->fundingCode(), $role)->get($action->section);
        if (! $rule) {
            return 'missing_rule';
        }

        $dateIssue = $this->dateIssue($student, $rule->available_after);
        if ($dateFirst && $dateIssue) {
            return $dateIssue;
        }

        if ($rule->requires_previous_section && ! $ignorePreviousSections) {
            foreach (array_slice(['A', 'B', 'G', 'D'], 0, array_search($action->section, ['A', 'B', 'G', 'D'], true)) as $section) {
                if (! $this->sectionIsComplete($student, $section, $role)) {
                    $sectionLabel = ['A' => 'Α', 'B' => 'Β', 'G' => 'Γ', 'D' => 'Δ'][$section];

                    return "previous_section:{$sectionLabel}";
                }
            }
        }

        if (! $action->prerequisitesAreCompleteFor($student, $actions)) {
            return 'previous_step';
        }

        return $dateFirst ? null : $dateIssue;
    }

    /** @return Collection<int, WorkflowAction> */
    private function actionsFor(string $funding): Collection
    {
        return $this->actionsByFunding[$funding] ??= WorkflowAction::activeForFunding($funding);
    }

    /** @return Collection<string, WorkflowSectionRule> */
    private function rulesFor(string $funding, string $role): Collection
    {
        $context = $funding.'|'.$role;

        return $this->rulesByContext[$context] ??= WorkflowSectionRule::query()
            ->where('role', $role)
            ->whereIn('funding_code', ['all', $funding])
            ->orderByRaw('funding_code = ? desc', [$funding])
            ->get()
            ->unique('section')
            ->keyBy('section');
    }

    /** @return array<string, mixed> */
    private function row(Student $student, WorkflowAction $action, string $currentRole): array
    {
        $status = $action->statusFor($student);
        $done = $action->isCompleteFor($student);

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
            'blockedMessage' => $this->blockedMessage($student, $action, $currentRole, $status),
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

    private function blockedMessage(Student $student, WorkflowAction $action, string $role, ?string $status): ?string
    {
        if ($reason = $this->unavailableReason($student, $action->code, $role)) {
            return $reason;
        }

        if ($action->code === 'katastash_praktikis' && ! $this->actionComplete($student, 'aitima_plhrwmhs')) {
            return 'Για να ολοκληρωθεί η πρακτική πρέπει πρώτα να ολοκληρωθεί η κατάσταση αιτήματος πληρωμής.';
        }

        if ($status === 'submitted' && in_array($action->source_type, ['document', 'action'], true)) {
            return 'Αναμονή ελέγχου από το Γραφείο Πρακτικής Άσκησης.';
        }

        return null;
    }

    private function dateUnavailableReason(Student $student, WorkflowAction $action, string $availableAfter): ?string
    {
        if ($availableAfter === 'always') {
            return null;
        }

        if (str_starts_with($availableAfter, 'missing_')) {
            return $availableAfter === 'missing_start_date'
                ? 'Δεν έχει οριστεί ημερομηνία έναρξης της πρακτικής.'
                : 'Δεν έχει οριστεί ημερομηνία λήξης της πρακτικής.';
        }

        if (in_array($action->source_type, ['student_questionnaire', 'provider_questionnaire'], true)) {
            return 'Το ερωτηματολόγιο ανοίγει μετά τη λήξη της πρακτικής.';
        }

        return $availableAfter === 'internship_not_started'
            ? 'Δεν έχει φτάσει ακόμη η ημερομηνία έναρξης της πρακτικής σας.'
            : 'Δεν έχει φτάσει ακόμη η ημερομηνία λήξης της πρακτικής σας.';
    }

    private function dateIssue(Student $student, string $availableAfter): ?string
    {
        if ($availableAfter === 'always') {
            return null;
        }

        $date = $availableAfter === 'internship_start' ? $student->startDate : $student->endDate;

        if (! $date) {
            return $availableAfter === 'internship_start' ? 'missing_start_date' : 'missing_end_date';
        }

        if (now()->gte($date)) {
            return null;
        }

        return $availableAfter === 'internship_start' ? 'internship_not_started' : 'internship_active';
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
