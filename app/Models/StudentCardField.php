<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentCardField extends Model
{
    protected $table = 'fields';

    protected $guarded = [];

    /** @param Builder<StudentCardField> $query */
    public function scopeForCardSection(Builder $query, string $cardSection): void
    {
        $query->where('card_section', $cardSection);
    }

    /** @param Builder<StudentCardField> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /** @param Builder<StudentCardField> $query */
    public function scopeVisibleFor(Builder $query, string $funding, string $role): void
    {
        $query
            ->where(fn (Builder $query) => $query
                ->whereJsonContains('fundings', 'all')
                ->orWhereJsonContains('fundings', $funding))
            ->where(fn (Builder $query) => $query
                ->whereJsonContains('visible_to_roles', 'all')
                ->orWhereJsonContains('visible_to_roles', $role));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'fundings' => 'array',
            'visible_to_roles' => 'array',
        ];
    }
}
