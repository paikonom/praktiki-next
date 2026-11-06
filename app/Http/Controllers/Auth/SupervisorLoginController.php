<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ScientificSupervisor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SupervisorLoginController extends Controller
{
    public function create(): View
    {
        return view('auth.scientific-supervisor-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'access_code' => ['required', 'string', 'max:20'],
        ]);
        $code = trim($validatedData['access_code']);

        $supervisor = ScientificSupervisor::query()
            ->where('access_code', $code)
            ->first();

        if (! $supervisor) {
            throw ValidationException::withMessages([
                'access_code' => 'Ο κωδικός δεν είναι έγκυρος.',
            ]);
        }

        Auth::guard('scientific_supervisor')->login($supervisor);
        $supervisor->forceFill(['last_login_at' => now()]);
        $supervisor->save();
        $request->session()->regenerate();

        return redirect()->intended(route('scientific_supervisor.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('scientific_supervisor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
