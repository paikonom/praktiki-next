<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderQuestionnaire extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'provider_questionnaires';

    protected $guarded = [];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 's_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(StudentProvider::class, 'p_id');
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'submitted_at' => 'datetime',
        ];
    }
}
