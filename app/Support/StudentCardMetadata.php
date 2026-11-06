<?php

namespace App\Support;

use App\Models\StudentCardField;
use Illuminate\Support\Collection;

class StudentCardMetadata
{
    /**
     * @param  array<int, string>|null  $allowedFields
     * @return Collection<int, StudentCardField>
     */
    public function fields(
        string $cardSection,
        string $funding,
        string $role,
        ?array $allowedFields = null,
    ): Collection {
        if ($allowedFields === []) {
            return collect();
        }

        $query = StudentCardField::query()
            ->forCardSection($cardSection)
            ->active()
            ->visibleFor($funding, $role);

        if ($allowedFields !== null) {
            $query->whereIn('field', $allowedFields);
        }

        return $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** @param Collection<int, StudentCardField> $fields
     * @return array<string, string>
     */
    public function labels(Collection $fields): array
    {
        return $fields->pluck('label', 'field')->all();
    }

    /** @param Collection<int, StudentCardField> $fields
     * @return array<string, array<int, string>>
     */
    public function groups(Collection $fields): array
    {
        return $fields
            ->groupBy(fn (StudentCardField $field): string => $field->section ?: 'Λοιπά στοιχεία')
            ->map(fn (Collection $group): array => $group->pluck('field')->all())
            ->all();
    }

    /** @param Collection<int, StudentCardField> $fields
     * @return array<string, string|null>
     */
    public function help(Collection $fields): array
    {
        return $fields->pluck('help_text', 'field')->all();
    }
}
