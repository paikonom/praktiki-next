<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckStudentImportRequest;
use App\Models\Fasi;
use App\Models\Praktiki;
use App\Models\Student;
use App\Models\StudentProvider;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentImportController extends Controller
{
    public function create(Request $request): View
    {
        return $this->form($request->user());
    }

    public function check(CheckStudentImportRequest $request): View
    {
        $data = $request->validated();
        $phase = $this->selectedPhase((int) $data['phase_id'], (int) $data['department_id']);

        $rows = $this->checkRows($data['students'], $phase);

        return $this->form(
            $request->user(),
            $rows,
            (int) $data['department_id'],
            $phase->id,
            $data['students'],
        );
    }

    public function store(CheckStudentImportRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $phase = $this->selectedPhase((int) $data['phase_id'], (int) $data['department_id']);

        $inserted = DB::transaction(function () use ($data, $phase): int {
            Fasi::query()->whereKey($phase->id)->lockForUpdate()->firstOrFail();
            $phase->refresh()->load(['department.activeScientificSupervisor', 'praktiki.funding']);
            $rows = $this->checkRows($data['students'], $phase);

            if ($rows->contains('valid', false)) {
                throw ValidationException::withMessages([
                    'students' => 'Η εισαγωγή σταμάτησε. Κάντε ξανά έλεγχο για να δείτε τις γραμμές με πρόβλημα.',
                ]);
            }

            foreach ($rows as $row) {
                $accessCode = $this->newProviderAccessCode();
                $supervisorId = $phase->department->activeScientificSupervisor->id;

                $student = (new Student)->forceFill([
                    'department_id' => $phase->department_id,
                    'fash_id' => $phase->id,
                    'supervisor_id' => $supervisorId,
                    'uuid' => (string) Str::uuid(),
                    'AMF' => $row['amf'],
                    'tel' => $row['telephone'],
                    'email' => $row['email'],
                    'epopt_kath' => $row['supervisor'],
                    'kwdForea' => $accessCode,
                    'provider_access_token_hash' => hash('sha256', $accessCode),
                    'doseis_count' => $phase->funding_code === 'Espa' ? 1 : null,
                ]);
                $student->save();
                $student->provider()->save(new StudentProvider);
            }

            return $rows->count();
        });

        return redirect()->route('students.index', [
            'year' => $phase->praxh_year,
            'funding' => $phase->funding_code,
            'department_id' => $phase->department_id,
            'phase' => $phase->name,
        ])->with('status', "Εισήχθησαν επιτυχώς {$inserted} φοιτητές.");
    }

    /** @return Collection<int, Fasi> */
    private function availablePhases(User $user): Collection
    {
        return Fasi::query()
            ->with(['department.activeScientificSupervisor', 'praktiki.funding'])
            ->whereHas('department')
            ->whereHas('praktiki.funding')
            ->orderByDesc(
                Praktiki::query()
                    ->select('praxh_year')
                    ->whereColumn('praktiki_cycles.id', 'praktiki_phases.praktiki_cycle_id')
                    ->limit(1),
            )
            ->orderBy('department_id')
            ->orderBy('name')
            ->get()
            ->filter(fn (Fasi $phase): bool => Gate::forUser($user)->allows('update', $phase))
            ->values();
    }

    /** @param Collection<int, array<string, mixed>>|null $rows */
    private function form(
        User $user,
        ?Collection $rows = null,
        ?int $selectedDepartmentId = null,
        ?int $selectedPhaseId = null,
        string $studentsText = '',
    ): View {
        $phases = $this->availablePhases($user);
        $departments = $phases
            ->pluck('department')
            ->unique('id')
            ->sortBy('tmhma')
            ->values();

        return view('students.import', compact(
            'departments', 'phases', 'rows', 'selectedDepartmentId', 'selectedPhaseId', 'studentsText',
        ));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function checkRows(string $text, Fasi $phase): Collection
    {
        $seenAmfs = [];

        return collect(preg_split('/\R/u', trim($text)))
            ->filter(fn (string $line): bool => trim($line) !== '')
            ->values()
            ->map(function (string $line, int $index) use ($phase, &$seenAmfs): array {
                $columns = array_map('trim', explode("\t", $line));
                $values = array_pad(array_slice($columns, 0, 4), 4, '');
                [$amf, $telephone, $email, $supervisor] = $values;

                $validator = Validator::make([
                    'amf' => $amf,
                    'telephone' => $telephone,
                    'email' => $email,
                    'supervisor' => $supervisor,
                ], [
                    'amf' => ['required', 'digits:13'],
                    'telephone' => ['required', 'regex:/^\+?[0-9 ]{10,15}$/'],
                    'email' => ['required', 'email:rfc', 'max:50'],
                    'supervisor' => ['required', 'string', 'max:100'],
                ], [
                    'amf.required' => 'Λείπει ο ΑΜ.',
                    'amf.digits' => 'Ο ΑΜ πρέπει να έχει 13 ψηφία.',
                    'telephone.required' => 'Λείπει το τηλέφωνο.',
                    'telephone.regex' => 'Το τηλέφωνο δεν είναι έγκυρο.',
                    'email.required' => 'Λείπει το email.',
                    'email.email' => 'Το email δεν είναι έγκυρο.',
                    'email.max' => 'Το email είναι πολύ μεγάλο.',
                    'supervisor.required' => 'Λείπει ο επόπτης καθηγητής.',
                    'supervisor.max' => 'Το όνομα του επόπτη είναι πολύ μεγάλο.',
                ]);

                if (count($columns) !== 4) {
                    $validator->errors()->add('columns', 'Η γραμμή πρέπει να έχει ακριβώς 4 στήλες.');
                }

                if ($amf !== '' && in_array($amf, $seenAmfs, true)) {
                    $validator->errors()->add('amf', 'Ο ίδιος ΑΜ υπάρχει περισσότερες από μία φορές στην επικόλληση.');
                }

                $seenAmfs[] = $amf;

                if (! $phase->department?->activeScientificSupervisor) {
                    $validator->errors()->add('phase', 'Δεν έχει οριστεί ενεργός επιστημονικός υπεύθυνος για το τμήμα.');
                }

                $duplicate = $amf !== '' && Student::query()
                    ->where('AMF', $amf)
                    ->where('fash_id', $phase->id)
                    ->whereDoesntHave('actions', fn ($query) => $query
                        ->where('action', 'katastash_praktikis')
                        ->where('status', 'cancelled'))
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add('amf', 'Υπάρχει ήδη ενεργή εγγραφή του ΑΜ σε αυτή τη φάση.');
                }

                return [
                    'line' => $index + 1,
                    'amf' => $amf,
                    'telephone' => $telephone,
                    'email' => $email,
                    'supervisor' => $supervisor,
                    'errors' => $validator->errors()->all(),
                    'valid' => ! $validator->errors()->any(),
                ];
            });
    }

    private function selectedPhase(int $phaseId, int $departmentId): Fasi
    {
        $phase = Fasi::query()
            ->with(['department.activeScientificSupervisor', 'praktiki.funding'])
            ->findOrFail($phaseId);

        Gate::authorize('update', $phase);
        abort_unless($phase->department_id === $departmentId, 422);

        return $phase;
    }

    private function newProviderAccessCode(): string
    {
        do {
            $accessCode = Str::random(16);
        } while (Student::query()->where('provider_access_token_hash', hash('sha256', $accessCode))->exists());

        return $accessCode;
    }
}
