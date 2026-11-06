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
    public function create(): View
    {
        $this->ensureDevelopmentEnvironment();

        return view('auth.student-login');
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

        return redirect()->route('student.internships');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureDevelopmentEnvironment();

        $validationRules = [
            'AMF' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9]+$/',
                'exists:students,AMF',
            ],
        ];

        $validationMessages = [
            'AMF.required' => 'Ο αριθμός μητρώου είναι υποχρεωτικός.',
            'AMF.regex' => 'Ο αριθμός μητρώου πρέπει να περιέχει μόνο ψηφία.',
            'AMF.exists' => 'Δεν βρέθηκαν πρακτικές για αυτόν τον αριθμό μητρώου.',
        ];

        $validatedData = $request->validate($validationRules, $validationMessages);
        $studentRegistrationNumber = $validatedData['AMF'];

        $request->session()->put('student_login_amf', $studentRegistrationNumber);

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
            $request->session()->forget('student_login_amf');

            return redirect()->route('student.test_login')->withErrors([
                'AMF' => 'Η επιλεγμένη πρακτική έχει ακυρωθεί και δεν επιτρέπονται ενέργειες.',
            ]);
        }

        Auth::guard('student')->login($student);
        $request->session()->forget('student_login_amf');
        $request->session()->regenerate();

        return redirect()->intended(route('student.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function ensureDevelopmentEnvironment(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
    }
}
