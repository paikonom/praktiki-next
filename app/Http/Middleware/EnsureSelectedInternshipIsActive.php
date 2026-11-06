<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSelectedInternshipIsActive
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $student = $request->user('student');
        if (! $student?->isCancelled()) {
            return $next($request);
        }

        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.test_login')->withErrors([
            'AMF' => 'Η επιλεγμένη πρακτική έχει ακυρωθεί και δεν επιτρέπονται ενέργειες.',
        ]);
    }
}
