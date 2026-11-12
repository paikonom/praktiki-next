<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\StudentWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentAuthenticatedSessionController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $casResponse = $this->authenticateWithCas();
        $studentRegistrationNumber = $casResponse['attributes']['uoastudentid'] ?? null;

        if (! is_string($studentRegistrationNumber)
            || ! preg_match('/^[0-9]{1,20}$/', $studentRegistrationNumber)) {
            return $this->logoutCasWithError(
                'Το UOA δεν επέστρεψε έγκυρο αριθμό μητρώου για τον λογαριασμό σας.'
            );
        }

        if (! Student::query()->where('AMF', $studentRegistrationNumber)->exists()) {
            return $this->logoutCasWithError(
                'Δεν βρέθηκαν πρακτικές για τον αριθμό μητρώου του λογαριασμού σας.'
            );
        }

        $request->session()->put([
            'student_login_amf' => $studentRegistrationNumber,
            'student_login_source' => 'cas',
            'student_cas_response' => $casResponse,
        ]);

        return redirect()->route('student.internships');
    }

    public function test_create(): View
    {
        // $this->ensureDevelopmentEnvironment();

        return view('auth.test_student-login');
    }

    public function test_store(Request $request): RedirectResponse
    {
        $validationRules = [
            'AMF' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9]+$/',
            ],
            'uuid' => [
                'required',
                'uuid',
            ],
        ];

        $validationMessages = [
            'AMF.required' => 'Ο αριθμός μητρώου είναι υποχρεωτικός.',
            'AMF.regex' => 'Ο αριθμός μητρώου πρέπει να περιέχει μόνο ψηφία.',
            'uuid.required' => 'Το UUID είναι υποχρεωτικό.',
            'uuid.uuid' => 'Το UUID δεν έχει σωστή μορφή.',
        ];

        $validatedData = $request->validate($validationRules, $validationMessages);
        $studentRegistrationNumber = $validatedData['AMF'];
        $studentUuid = $validatedData['uuid'];

        $studentExists = Student::query()
            ->where('AMF', $studentRegistrationNumber)
            ->where('uuid', $studentUuid)
            ->exists();

        if (! $studentExists) {
            return back()
                ->withErrors(['AMF' => 'Ο αριθμός μητρώου ή το UUID δεν είναι σωστό.'])
                ->onlyInput('AMF');
        }

        $request->session()->put('student_login_amf', $studentRegistrationNumber);
        $request->session()->put('student_login_source', 'temporary');
        $request->session()->forget('student_cas_response');

        return redirect()->route('student.internships');
    }

    public function internships(Request $request): View|RedirectResponse
    {
        $amf = $request->session()->get('student_login_amf');

        if (! $amf) {
            return redirect()->route('student.test_login');
        }

        $students = Student::query()
            ->where('AMF', $amf)
            ->with(['department', 'phase.praktiki.funding', 'actions'])
            ->orderByDesc('id')
            ->get()
            ->sortByDesc(fn (Student $student): string => $student->praxh_year.'-'.str_pad((string) $student->id, 20, '0', STR_PAD_LEFT))
            ->values();

        if ($students->isEmpty()) {
            $request->session()->forget('student_login_amf');

            return redirect()->route('student.test_login')
                ->withErrors(['AMF' => 'Δεν βρέθηκαν διαθέσιμες πρακτικές.']);
        }

        $internshipStates = $students->mapWithKeys(fn (Student $student) => [
            $student->id => StudentWorkflow::currentState($student),
        ]);

        return view('auth.student-internships', compact('students', 'internshipStates'));
    }

    public function selectInternship(Request $request): RedirectResponse
    {
        $amf = $request->session()->get('student_login_amf');

        if (! $amf) {
            return redirect()->route('student.test_login');
        }

        $validated = $request->validate(['student_id' => ['required', 'integer']]);
        $student = Student::query()
            ->where('AMF', $amf)
            ->findOrFail($validated['student_id']);

        if ($student->isCancelled()) {
            $request->session()->forget(['student_login_amf', 'student_cas_response']);

            return redirect()->route('student.test_login')->withErrors([
                'AMF' => 'Η επιλεγμένη πρακτική έχει ακυρωθεί και δεν επιτρέπονται ενέργειες.',
            ]);
        }

        if ($request->session()->get('student_login_source') === 'cas') {
            $student->data_CAS = $request->session()->get('student_cas_response');
            $student->save();
        }

        Auth::guard('student')->login($student);
        $request->session()->forget(['student_login_amf', 'student_cas_response']);
        $request->session()->regenerate();

        return redirect()->intended(route('student.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $loggedInWithCas = $request->session()->get('student_login_source') === 'cas';

        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($loggedInWithCas) {
            $this->initializeCas();
            \phpCAS::logoutWithRedirectService(route('home'));
        }

        return redirect()->route('home');
    }

    /** @return array{user: string, attributes: array<string, mixed>} */
    private function authenticateWithCas(): array
    {
        $this->initializeCas();

        $laravelErrorHandler = null;
        $laravelErrorHandler = set_error_handler(
            function (int $severity, string $message, string $file, int $line) use (&$laravelErrorHandler): bool {
                if ($severity === E_WARNING
                    && str_contains($file, '/apereo/phpcas/')
                    && $message === 'Undefined array key 0') {
                    return true;
                }

                return is_callable($laravelErrorHandler)
                    ? (bool) $laravelErrorHandler($severity, $message, $file, $line)
                    : false;
            },
        );

        try {
            \phpCAS::forceAuthentication();

            return [
                'user' => \phpCAS::getUser(),
                'attributes' => \phpCAS::getAttributes(),
            ];
        } finally {
            restore_error_handler();
        }
    }

    private function initializeCas(): void
    {
        \phpCAS::client(
            SAML_VERSION_1_1,
            config('services.cas.host'),
            config('services.cas.port'),
            '',
            config('app.url'),
            false,
        );
        \phpCAS::setCasServerCACert(config('services.cas.ca_cert'));
    }

    private function logoutCasWithError(string $message): RedirectResponse
    {
        $redirect = redirect()->route('home')->withErrors(['login' => $message]);

        \phpCAS::logoutWithRedirectService($redirect->getTargetUrl());

        return $redirect;
    }

}
