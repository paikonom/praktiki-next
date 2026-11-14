<?php

namespace App\Models;

use App\Support\WorkflowDisplay;
use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['code', 'title', 'instructions', 'max_size_mb', 'active', 'sort_order'])]
class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use HasFactory;

    protected $table = 'document_types';

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(DocumentTypeRole::class);
    }

    public function fundings(): HasMany
    {
        return $this->hasMany(DocumentTypeFunding::class);
    }

    public function workflowActions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class, 'source_key', 'code')
            ->where('source_type', 'document')
            ->where('active', true);
    }

    public function isAvailableFor(Student $internshipStudent, ?string $loggedInRole = null): bool
    {
        return $this->availabilityIssueFor($internshipStudent, $loggedInRole) === null;
    }

    public function availabilityIssueFor(Student $internshipStudent, ?string $loggedInRole = null): ?string
    {
        if (! $this->active) {
            return 'inactive';
        }

        if (! $this->fundings()->where('funding_code', $internshipStudent->fundingCode())->exists()) {
            return 'funding';
        }

        $internshipStudent->loadMissing([
            'actions',
            'documents.type',
            'apografika',
            'questionnaire',
            'providerQuestionnaire',
        ]);
        $funding = $internshipStudent->fundingCode();
        $workflowAction = $this->workflowActionFor($funding);

        if ($workflowAction && $loggedInRole) {
            $issue = app(WorkflowDisplay::class)->unavailableIssue(
                $internshipStudent,
                $workflowAction,
                $loggedInRole,
                ignorePreviousSections: $loggedInRole === 'member',
                dateFirst: true,
            );

            if (str_starts_with((string) $issue, 'previous_section:')) {
                return 'previous_sections';
            }

            return $issue;
        }

        if ($workflowAction
            && ! $workflowAction->prerequisitesAreCompleteFor($internshipStudent, WorkflowAction::activeForFunding($funding))) {
            return 'previous_step';
        }

        return null;
    }

    public function roleCan(string $role, string $ability): bool
    {
        return $this->roles()->where('role', $role)->where($ability, true)->exists();
    }

    /** @return Collection<int, self> */
    public static function visibleForStudentAndRole(Student $student, string $role): Collection
    {
        return self::query()
            ->where('active', true)
            ->whereHas('fundings', fn ($query) => $query->where('funding_code', $student->fundingCode()))
            ->whereHas('roles', fn ($query) => $query->where('role', $role)->where('can_view', true))
            ->with(['roles', 'workflowActions', 'documents' => fn ($query) => $query->where('s_id', $student->id)->latest('version')])
            ->orderBy('sort_order')
            ->get()
            ->sortBy(fn (DocumentType $documentType): float => $documentType->workflowOrder($student->fundingCode()))
            ->values();
    }

    /** @return Collection<int, string> */
    public static function requiredEndDocumentCodes(Student $student, string $role): Collection
    {
        return self::query()
            ->where('active', true)
            ->whereHas('fundings', fn ($query) => $query->where('funding_code', $student->fundingCode()))
            ->whereHas('roles', fn ($query) => $query->where('role', $role)->where('can_upload', true))
            ->whereHas('workflowActions', fn ($query) => $query
                ->where('section', 'G')
                ->whereIn('funding_code', ['all', $student->fundingCode()]))
            ->pluck('code');
    }

    public function period(string $funding = ''): string
    {
        if ($this->code === 'extra_docs') {
            return 'extra';
        }

        $section = $this->workflowActionFor($funding)?->section;

        if ($section) {
            return match ($section) {
                'A' => 'before',
                'B' => 'during',
                default => 'after',
            };
        }

        return 'before';
    }

    public function periodLabel(string $funding = ''): string
    {
        return match ($this->period($funding)) {
            'before' => 'Πριν την έναρξη',
            'during' => 'Κατά τη διάρκεια',
            'after' => 'Μετά τη λήξη',
            'extra' => 'Πρόσθετα έγγραφα',
        };
    }

    public function workflowOrder(string $funding): float
    {
        $action = $this->workflowActionFor($funding);

        if (! $action) {
            return 99999 + $this->sort_order;
        }

        $sectionOrder = array_search($action->section, ['A', 'B', 'G', 'D'], true);

        return $sectionOrder * 1000 + (float) $action->sort_order;
    }

    private function workflowActionFor(string $funding): ?WorkflowAction
    {
        $this->loadMissing('workflowActions');

        return $this->workflowActions->firstWhere('funding_code', $funding)
            ?? $this->workflowActions->firstWhere('funding_code', 'all');
    }

    public function acceptsNewVersionFor(Student $student): bool
    {
        if ($this->code === 'extra_docs') {
            return true;
        }

        $latestStatus = $this->documents()
            ->where('s_id', $student->id)
            ->latest('version')
            ->value('status');

        return ! in_array($latestStatus, ['submitted', 'approved', 'not_required'], true);
    }

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'decimal:2'];
    }
}
