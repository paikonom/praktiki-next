<?php

namespace App\Policies;

use App\Models\DocumentType;
use App\Models\ScientificSupervisor;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;

class StudentDocumentPolicy
{
    public function upload(
        User|Student|ScientificSupervisor $loggedInUser,
        DocumentType $documentType,
        Student $internshipStudent,
        string $loggedInRole,
    ): bool {
        return $this->userCanAccessStudent($loggedInUser, $internshipStudent, $loggedInRole)
            && $documentType->isAvailableFor($internshipStudent, $loggedInRole)
            && $documentType->roleCan($loggedInRole, 'can_upload')
            && $documentType->acceptsNewVersionFor($internshipStudent);
    }

    public function view(User|Student|ScientificSupervisor $loggedInUser, StudentDocument $studentDocument, string $loggedInRole): bool
    {
        return $this->userCanAccessStudent($loggedInUser, $studentDocument->student, $loggedInRole)
            && filled($studentDocument->path)
            && $studentDocument->type->roleCan($loggedInRole, 'can_view');
    }

    public function delete(User|Student|ScientificSupervisor $loggedInUser, StudentDocument $studentDocument, string $loggedInRole): bool
    {
        if ($loggedInUser instanceof User && $loggedInUser->role === 'admin') {
            return true;
        }

        if ($loggedInUser instanceof User && $loggedInRole === 'member') {
            return in_array($studentDocument->status, ['submitted', 'approved'], true)
                && app(StudentPolicy::class)->update($loggedInUser, $studentDocument->student);
        }

        return $studentDocument->status === 'submitted'
            && $studentDocument->uploaded_by_role === $loggedInRole
            && $studentDocument->uploaded_by_id === $loggedInUser->id
            && $this->userCanAccessStudent($loggedInUser, $studentDocument->student, $loggedInRole);
    }

    public function review(User $loggedInUser, StudentDocument $studentDocument): bool
    {
        return ! $loggedInUser->view_only
            && app(StudentPolicy::class)->update($loggedInUser, $studentDocument->student);
    }

    private function userCanAccessStudent(
        User|Student|ScientificSupervisor $loggedInUser,
        Student $internshipStudent,
        string $loggedInRole,
    ): bool {
        if ($loggedInUser instanceof Student) {
            return in_array($loggedInRole, ['student', 'provider'], true)
                && $loggedInUser->is($internshipStudent);
        }

        if ($loggedInUser instanceof ScientificSupervisor) {
            return $loggedInRole === 'scientific_supervisor'
                && $internshipStudent->supervisor_id === $loggedInUser->id;
        }

        return app(StudentPolicy::class)->view($loggedInUser, $internshipStudent);
    }
}
