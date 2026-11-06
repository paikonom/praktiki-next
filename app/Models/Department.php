<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Department extends Model
{
    protected $table = 'departments';

    public function activeScientificSupervisor(): HasOne
    {
        return $this->hasOne(ScientificSupervisor::class)->where('active', true);
    }

    public function scientificSupervisors(): HasMany
    {
        return $this->hasMany(ScientificSupervisor::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_department_access');
    }
}
