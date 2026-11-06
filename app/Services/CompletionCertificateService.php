<?php

namespace App\Services;

use App\Models\Student;

class CompletionCertificateService
{
    /** @return array<string, mixed> */
    public function providerData(Student $student): array
    {
        $student->loadMissing(['department', 'provider', 'phase.praktiki.funding']);

        return [
            'student' => $student,
            'provider' => $student->provider,
            'department' => $student->department,
            'funding' => $student->fundingCode(),
        ];
    }

    /** @return array<string, mixed> */
    public function supervisorData(Student $student): array
    {
        $student->loadMissing(['department', 'provider', 'supervisor', 'phase.praktiki.funding']);
        abort_unless($student->fundingCode() === 'Espa', 404);

        return [
            'student' => $student,
            'provider' => $student->provider,
            'supervisor' => $student->supervisor,
            'department' => $student->department,
            'details' => $student->phase?->praktiki?->details ?? [],
        ];
    }
}
