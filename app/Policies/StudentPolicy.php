<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function view(User $user, Student $student): bool
    {
        return $user->role === 'admin'
            || $user->departments()->whereKey($student->department_id)->exists();
    }

    public function update(User $user, Student $student): bool
    {
        return ! $user->view_only && $this->view($user, $student);
    }
}
