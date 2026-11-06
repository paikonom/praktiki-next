<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProviderAuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.provider-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'access_code' => ['required', 'string', 'max:100'],
        ], ['access_code.required' => 'Ο μοναδικός κωδικός είναι υποχρεωτικός.']);

        $accessCode = trim($credentials['access_code']);
        $accessCodeHash = hash('sha256', $accessCode);
        $student = Student::query()
            ->where('provider_access_token_hash', $accessCodeHash)
            ->first();

        if ($student && ! hash_equals(hash('sha256', trim((string) $student->kwdForea)), $accessCodeHash)) {
            $student = null;
        }

        $student ??= Student::query()
            ->whereRaw('TRIM(kwdForea) = ?', [$accessCode])
            ->first();

        if (! $student) {
            throw ValidationException::withMessages(['access_code' => 'Ο κωδικός δεν είναι έγκυρος.']);
        }

        if (! hash_equals((string) $student->provider_access_token_hash, $accessCodeHash)) {
            $student->forceFill(['provider_access_token_hash' => $accessCodeHash])->save();
        }

        Auth::guard('provider')->login($student);
        $request->session()->regenerate();

        return redirect()->intended(route('provider.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('provider')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
