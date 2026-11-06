<?php

namespace App\Policies;

use App\Models\Fasi;
use App\Models\User;

class FasiPolicy
{
    public function update(User $user, Fasi $fasi): bool
    {
        if ($user->view_only) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $user->manageableDepartments($fasi->funding_code)
            ->whereKey($fasi->department_id)
            ->exists();
    }
}
