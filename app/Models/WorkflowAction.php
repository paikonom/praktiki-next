<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

#[Fillable(['funding_code', 'section', 'sort_order', 'code', 'title', 'responsible_role', 'prerequisites', 'source_type', 'source_key', 'allowed_statuses', 'completed_statuses', 'counts_for_section', 'active'])]
class WorkflowAction extends Model
{
    protected $table = 'workflow_actions';

    /** @return Collection<int, self> */
    public static function activeForFunding(string $funding): Collection
    {
        return self::query()
            ->where('active', true)
            ->whereIn('funding_code', ['all', $funding])
            ->orderByRaw('funding_code = ? desc', [$funding])
            ->get()
            ->unique('code')
            ->sortBy(fn (self $action): array => [
                array_search($action->section, ['A', 'B', 'G', 'D'], true),
                (float) $action->sort_order,
            ])
            ->values();
    }

    public static function statusIsAllowedFor(
        Student $student,
        string $sourceType,
        string $sourceKey,
        string $status,
    ): bool {
        $action = self::activeForFunding($student->fundingCode())
            ->first(fn (self $action): bool => $action->source_type === $sourceType
                && $action->source_key === $sourceKey);

        return $action !== null
            && in_array($status, $action->allowed_statuses ?? [], true);
    }

    /**
     * @param  Collection<int, self>  $availableActions
     * @return array{section: string, pendingActions: Collection<int, self>, approvalAction: self|null}|null
     */
    public static function nextIncompleteSectionFor(Student $student, Collection $availableActions): ?array
    {
        foreach (['A', 'B', 'G', 'D'] as $section) {
            $sectionActions = $availableActions->where('section', $section);
            $pendingActions = $sectionActions
                ->where('counts_for_section', true)
                ->reject(fn (self $action): bool => $action->isCompleteFor($student))
                ->values();

            if ($pendingActions->isNotEmpty()) {
                return [
                    'section' => $section,
                    'pendingActions' => $pendingActions,
                    'approvalAction' => null,
                ];
            }
        }

        return null;
    }

    public function statusFor(Student $student): ?string
    {
        return match ($this->source_type) {
            'action' => $student->action($this->source_key)?->status,
            'document' => $student->latestDocumentStatus($this->source_key),
            'apografiko' => $student->apografika->firstWhere('type', $this->source_key)?->status,
            'student_questionnaire' => $student->questionnaire?->status,
            'provider_questionnaire' => $student->providerQuestionnaire?->status,
            default => null,
        };
    }

    public function isCompleteFor(Student $student): bool
    {
        return in_array($this->statusFor($student), $this->completed_statuses ?? [], true);
    }

    /** @param Collection<int, self> $availableActions */
    public function prerequisitesAreCompleteFor(Student $student, Collection $availableActions): bool
    {
        return collect($this->prerequisites)->every(function (string $requiredActionCode) use ($student, $availableActions): bool {
            $requiredAction = $availableActions->firstWhere('code', $requiredActionCode);

            return $requiredAction?->isCompleteFor($student) ?? false;
        });
    }

    protected function casts(): array
    {
        return [
            'prerequisites' => 'array', 'allowed_statuses' => 'array', 'completed_statuses' => 'array',
            'counts_for_section' => 'boolean',
            'active' => 'boolean', 'sort_order' => 'decimal:2',
        ];
    }
}
