<?php

namespace App\Policies;

use App\Models\Praktiki;
use App\Models\User;

class PraktikiPolicy
{
    public function create(User $user): bool
    {
        return $user->role === 'admin' && ! $user->view_only;
    }

    public function update(User $user, Praktiki $praktiki): bool
    {
        return $this->create($user);
    }
}
