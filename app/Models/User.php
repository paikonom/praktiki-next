<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'telephone', 'password', 'role', 'view_only'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    private const FUNDING_MANAGEMENT_COLUMNS = [
        'Espa' => 'can_manage_espa',
        'Institution' => 'can_manage_institution',
        'InternshipProvider' => 'can_manage_internship_provider',
    ];

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'user_department_access');
    }

    public function manageableDepartments(string $funding): BelongsToMany
    {
        return $this->departments()
            ->wherePivot(self::FUNDING_MANAGEMENT_COLUMNS[$funding], true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'view_only' => 'boolean',
        ];
    }
}
