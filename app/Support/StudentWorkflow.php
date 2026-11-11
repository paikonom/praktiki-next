<?php

namespace App\Support;

use App\Models\Student;

class StudentWorkflow
{
    public static function currentState(Student $student): string
    {
        if ($student->isCancelled()) {
            return 'canceled';
        }

        if ($student->isLegacy()) {
            return 'legacy';
        }

        if ($student->isCompleted()) {
            return 'completed';
        }

        if (! $student->startDate || ! $student->endDate || now()->lt($student->startDate)) {
            return 'before';
        }

        return now()->lt($student->endDate) ? 'during' : 'after';
    }
}
