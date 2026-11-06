<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['funding_code', 'section', 'role', 'available_after', 'requires_previous_section'])]
class WorkflowSectionRule extends Model
{
    protected $table = 'workflow_section_rules';

    public static function forContext(string $funding, string $section, string $role): ?self
    {
        return self::query()
            ->where('section', $section)
            ->where('role', $role)
            ->whereIn('funding_code', ['all', $funding])
            ->orderByRaw('funding_code = ? desc', [$funding])
            ->first();
    }

    protected function casts(): array
    {
        return ['requires_previous_section' => 'boolean'];
    }
}
