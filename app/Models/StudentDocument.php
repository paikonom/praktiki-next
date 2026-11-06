<?php

namespace App\Models;

use Database\Factories\StudentDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['s_id', 'document_type_id', 'version', 'disk', 'path', 'original_name', 'mime_type', 'size', 'status', 'uploaded_by_role', 'uploaded_by_id', 'uploaded_at', 'reviewed_by', 'reviewed_at', 'review_notes'])]
class StudentDocument extends Model
{
    /** @use HasFactory<StudentDocumentFactory> */
    use HasFactory;

    protected $table = 'student_documents';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 's_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }
}
