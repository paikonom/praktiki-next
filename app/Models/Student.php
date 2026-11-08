<?php

namespace App\Models;

use App\Support\WorkflowDisplay;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;

#new gia rescom
use App\Support\RescomReferenceData;

class Student extends Authenticatable
{
    protected $table = 'students';

    /** @var array<string, string|null>|null */
    private ?array $preloadedWorkflowStatuses = null;

    /** @var array<string, int>|null */
    private ?array $preloadedSentEmailCounts = null;

    public function studentAddress(): string
    {
        
        if (filled($this->address_polh_rescom) && filled($this->address_odos) && filled($this->address_tk)) {
            return collect([
                app(RescomReferenceData::class)->label('cities', $this->address_polh_rescom),
                $this->address_odos,
               'ΤΚ ' . $this->address_tk,
            ])->filter()->implode(', ');
        }
        return '';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(Fasi::class, 'fash_id');
    }

    public function provider(): HasOne
    {
        return $this->hasOne(StudentProvider::class, 's_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(ScientificSupervisor::class);
    }

    public function isCancelled(): bool
    {
        return $this->actionHasStatus('katastash_praktikis', 'cancelled');
    }

    public function isCompleted(): bool
    {
        return $this->actionHasStatus('katastash_praktikis', 'completed');
    }

    public function internshipStatus(): string
    {
        return $this->workflowStatus('action', 'katastash_praktikis') ?? 'active';
    }

    public function isLegacy(): bool
    {
        $startYear = (int) substr((string) $this->praxh_year, 0, 4);

        return $startYear > 0 && $startYear < 2024;
    }

    public function actions(): HasMany
    {
        return $this->hasMany(StudentAction::class);
    }

    public function action(string $code): ?StudentAction
    {
        $this->loadMissing('actions');

        return $this->actions->firstWhere('action', $code);
    }

    public function actionHasStatus(string $code, string|array $statuses): bool
    {
        return in_array($this->workflowStatus('action', $code), (array) $statuses, true);
    }

    public function actionIsComplete(string $code): bool
    {
        return in_array($this->workflowStatus('action', $code), ['approved', 'not_required'], true);
    }

    /** @param array<string, string|null> $statuses */
    public function setPreloadedWorkflowStatuses(array $statuses): void
    {
        $this->preloadedWorkflowStatuses = $statuses;
    }

    public function workflowStatus(string $sourceType, string $sourceKey): ?string
    {
        if ($this->preloadedWorkflowStatuses !== null) {
            return $this->preloadedWorkflowStatuses["{$sourceType}:{$sourceKey}"] ?? null;
        }

        return match ($sourceType) {
            'action' => $this->action($sourceKey)?->status,
            'document' => $this->latestDocumentStatus($sourceKey),
            'apografiko' => $this->apografika->firstWhere('type', $sourceKey)?->status,
            'student_questionnaire' => $this->questionnaire?->status,
            'provider_questionnaire' => $this->providerQuestionnaire?->status,
            default => null,
        };
    }

    /** @param array<string, int> $counts */
    public function setPreloadedSentEmailCounts(array $counts): void
    {
        $this->preloadedSentEmailCounts = $counts;
    }

    /** @return Collection<string, int> */
    public function sentEmailCounts(): Collection
    {
        if ($this->preloadedSentEmailCounts !== null) {
            return collect($this->preloadedSentEmailCounts);
        }

        $this->loadMissing('emailLogs');

        return $this->emailLogs->where('status', 'sent')->countBy('type');
    }

    public function questionnaire(): HasOne
    {
        return $this->hasOne(StudentQuestionnaire::class, 's_id');
    }

    public function providerQuestionnaire(): HasOne
    {
        return $this->hasOne(ProviderQuestionnaire::class, 's_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 's_id');
    }

    public function apografika(): HasMany
    {
        return $this->hasMany(StudentApografiko::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function handler(): ?User
    {
        $managementColumn = match ($this->fundingCode()) {
            'Espa' => 'can_manage_espa',
            'Institution' => 'can_manage_institution',
            'InternshipProvider' => 'can_manage_internship_provider',
            default => null,
        };

        if (! $this->department_id || ! $managementColumn) {
            return null;
        }

        $handlers = User::query()
            ->whereHas('departments', fn ($query) => $query
                ->where('departments.id', $this->department_id)
                ->where("user_department_access.{$managementColumn}", true))
            ->limit(2)
            ->get();

        return $handlers->count() === 1 ? $handlers->first() : null;
    }

    public function hasApprovedDocument(string $code): bool
    {
        return $this->latestDocumentStatus($code) === 'approved';
    }

    public function hasCompletedDocument(string $code): bool
    {
        return in_array($this->latestDocumentStatus($code), ['approved', 'not_required'], true);
    }

    /** @return Collection<int, string> */
    public function completedDocumentCodes(): Collection
    {
        $this->loadMissing('documents.type');

        return $this->documents
            ->filter(fn (StudentDocument $document): bool => filled($document->type?->code))
            ->groupBy(fn (StudentDocument $document): string => $document->type->code)
            ->map(fn (Collection $documents): ?StudentDocument => $documents->sortByDesc('version')->first())
            ->filter(fn (StudentDocument $document): bool => in_array($document->status, ['approved', 'not_required'], true))
            ->map(fn (StudentDocument $document): string => $document->type->code)
            ->values();
    }

    public function hasCompletedApografiko(string $type): bool
    {
        $this->loadMissing('apografika');

        return $this->apografika->firstWhere('type', $type)?->isFinal() ?? false;
    }

    public function providerBeforeStartComplete(): bool
    {
        return app(WorkflowDisplay::class)->sectionIsComplete($this, 'A', 'provider');
    }

    public function studentBeforeStartComplete(): bool
    {
        return app(WorkflowDisplay::class)->sectionIsComplete($this, 'A', 'student');
    }

    public function studentDuringComplete(): bool
    {
        return app(WorkflowDisplay::class)->sectionIsComplete($this, 'B', 'student');
    }

    public function providerDuringComplete(): bool
    {
        return $this->studentDuringComplete();
    }

    public function latestDocumentStatus(string $code): ?string
    {
        if ($this->preloadedWorkflowStatuses !== null) {
            return $this->preloadedWorkflowStatuses["document:{$code}"] ?? null;
        }

        if ($this->relationLoaded('documents')) {
            return $this->documents
                ->filter(fn (StudentDocument $document): bool => $document->type?->code === $code)
                ->sortByDesc('version')
                ->first()?->status;
        }

        return $this->documents()
            ->whereHas('type', fn ($query) => $query->where('code', $code))
            ->latest('version')
            ->value('status');
    }

    public function fundingCode(): string
    {
        return $this->attributes['funding_code']
            ?? $this->phase?->praktiki?->funding?->code
            ?? '';
    }

    protected function praxhYear(): Attribute
    {
        return Attribute::get(
            fn (?string $value): ?string => $value ?? $this->phase?->praktiki?->praxh_year,
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->lName} {$this->fName}"));
    }

    protected function durationDays(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (! $this->startDate || ! $this->endDate) {
                return null;
            }

            if ($this->diakoptomeno && $this->endDate_DKPT_A && $this->startDate_DKPT_B) {
                return (int) ($this->startDate->diffInDays($this->endDate_DKPT_A)
                    + $this->startDate_DKPT_B->diffInDays($this->endDate) + 2);
            }

            return (int) ($this->startDate->diffInDays($this->endDate) + 1);
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'startDate' => 'date',
            'endDate' => 'date',
            'diakoptomeno' => 'boolean',
            'endDate_DKPT_A' => 'date',
            'startDate_DKPT_B' => 'date',
            'numChild' => 'integer',
            'payPerMonth' => 'decimal:2',
            'payTotal' => 'decimal:2',
            'numMhnes' => 'decimal:2',
            'wresPerDays' => 'integer',
            'wresPerWeek' => 'integer',
            'questionnaire_version' => 'integer',
        ];
    }
}
