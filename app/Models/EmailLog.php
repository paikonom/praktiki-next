<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'sent_by_user_id', 'type', 'to', 'cc', 'subject', 'body', 'status', 'error_message', 'sent_at'])]
class EmailLog extends Model
{
    protected $table = 'email_logs';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['to' => 'array', 'cc' => 'array', 'sent_at' => 'datetime'];
    }
}
