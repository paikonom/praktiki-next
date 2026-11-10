<?php

namespace App\Services;

use App\Models\DocumentType;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StudentDocumentService
{
    public function store(
        Student $internshipStudent,
        DocumentType $documentType,
        UploadedFile $uploadedFile,
        Authenticatable $loggedInUser,
        string $loggedInRole,
    ): StudentDocument {
        $storedPath = null;

        try {
            return DB::transaction(function () use ($internshipStudent, $documentType, $uploadedFile, $loggedInUser, $loggedInRole, &$storedPath): StudentDocument {
                $version = (int) $internshipStudent->documents()->where('document_type_id', $documentType->id)->lockForUpdate()->max('version') + 1;
                $uploadedByMember = $loggedInRole === 'member';
                $studentDocument = StudentDocument::create([
                    's_id' => $internshipStudent->id,
                    'document_type_id' => $documentType->id,
                    'version' => $version,
                    'disk' => 'local',
                    'path' => 'pending',
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getMimeType() ?? 'application/pdf',
                    'size' => $uploadedFile->getSize(),
                    'status' => $uploadedByMember ? 'approved' : 'submitted',
                    'uploaded_by_role' => $loggedInRole,
                    'uploaded_by_id' => $loggedInUser->getAuthIdentifier(),
                    'uploaded_at' => now(),
                    'reviewed_by' => $uploadedByMember ? $loggedInUser->getAuthIdentifier() : null,
                    'reviewed_at' => $uploadedByMember ? now() : null,
                ]);
                $storedPath = $uploadedFile->storeAs("students/{$internshipStudent->id}/documents", "{$studentDocument->id}.pdf", 'local');
                if (! $storedPath) {
                    throw new RuntimeException('Το αρχείο δεν αποθηκεύτηκε.');
                }
                $studentDocument->update(['path' => $storedPath]);

                return $studentDocument;
            });
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function markNotRequired(Student $internshipStudent, DocumentType $documentType, Authenticatable $loggedInUser, string $reason): StudentDocument
    {
        return DB::transaction(function () use ($internshipStudent, $documentType, $loggedInUser, $reason): StudentDocument {
            $version = (int) $internshipStudent->documents()->where('document_type_id', $documentType->id)->lockForUpdate()->max('version') + 1;

            return StudentDocument::create([
                's_id' => $internshipStudent->id,
                'document_type_id' => $documentType->id,
                'version' => $version,
                'status' => 'not_required',
                'reviewed_by' => $loggedInUser->getAuthIdentifier(),
                'reviewed_at' => now(),
                'review_notes' => $reason,
            ]);
        });
    }

    public function delete(StudentDocument $studentDocument): void
    {
        if ($studentDocument->path && ! Storage::disk($studentDocument->disk)->delete($studentDocument->path)) {
            throw new RuntimeException('Το αρχείο δεν μπόρεσε να διαγραφεί.');
        }
        $studentDocument->delete();
    }
}
