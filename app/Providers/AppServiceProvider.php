<?php

namespace App\Providers;

use App\Models\Fasi;
use App\Models\Praktiki;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Policies\FasiPolicy;
use App\Policies\PraktikiPolicy;
use App\Policies\StudentDocumentPolicy;
use App\Policies\StudentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(StudentDocument::class, StudentDocumentPolicy::class);
        Gate::policy(Fasi::class, FasiPolicy::class);
        Gate::policy(Praktiki::class, PraktikiPolicy::class);
        Gate::define('view-admin', fn ($user): bool => $user instanceof User && $user->role === 'admin');
        Gate::define('viewLogViewer', fn (?User $user): bool => $user?->id === 1);

        RateLimiter::for('login', function (Request $request) {
            $username = Str::lower($request->string('username')->toString());

            return Limit::perMinute(5)->by(Str::transliterate($username).'|'.$request->ip());
        });
    }
}
