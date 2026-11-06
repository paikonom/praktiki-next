@extends('layouts.app')

@section('title', 'Έλεγχος νέων φοιτητών')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Έλεγχος νέων φοιτητών</h1>
            <p class="text-muted mb-0">Η επικόλληση αφορά ένα τμήμα και μία φάση. Ο έλεγχος δεν αποθηκεύει τίποτα.</p>
        </div>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Επιστροφή</a>
    </div>

    @if ($departments->isEmpty())
        <div class="alert alert-warning">Δεν έχετε κάποια φάση με δικαίωμα διαχείρισης.</div>
    @else
        <form method="POST" action="{{ route('students.import.check') }}" class="card border-0 shadow-sm mb-4">
            @csrf
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="department_id" class="form-label">Τμήμα</label>
                        <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                            <option value="">Επιλέξτε τμήμα</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((int) old('department_id', $selectedDepartmentId) === $department->id)>
                                    {{ $department->atlasId }} - {{ $department->tmhma }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="phase_id" class="form-label">Φάση</label>
                        <select id="phase_id" name="phase_id" class="form-select @error('phase_id') is-invalid @enderror" required>
                            <option value="">Επιλέξτε πρώτα τμήμα</option>
                            @foreach ($phases as $phase)
                                <option
                                    value="{{ $phase->id }}"
                                    data-department="{{ $phase->department_id }}"
                                    @selected((int) old('phase_id', $selectedPhaseId) === $phase->id)
                                >
                                    {{ $phase->praktiki->praxh_year }} · {{ $phase->praktiki->funding->name }} · Φάση {{ $phase->name ?: 'χωρίς όνομα' }}
                                </option>
                            @endforeach
                        </select>
                        @error('phase_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label for="students" class="form-label">Επικόλληση από Excel</label>
                        <div class="small text-muted mb-2">4 στήλες, χωρίς επικεφαλίδες: ΑΜ · Τηλέφωνο · Email · Επόπτης καθηγητής (Όνομα Επώνυμο)</div>
                        <textarea id="students" name="students" rows="10" class="form-control font-monospace @error('students') is-invalid @enderror" required>{{ old('students', $studentsText) }}</textarea>
                        @error('students')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white p-4">
                <button type="submit" class="btn btn-primary">Έλεγχος</button>
                <span class="small text-muted ms-2">Δεν γίνεται καμία εγγραφή στη βάση.</span>
            </div>
        </form>
    @endif

    @if ($rows !== null)
        @php($validCount = $rows->where('valid', true)->count())
        <div class="alert {{ $validCount === $rows->count() ? 'alert-success' : 'alert-warning' }}">
            Ελέγχθηκαν {{ $rows->count() }} γραμμές: {{ $validCount }} σωστές, {{ $rows->count() - $validCount }} με πρόβλημα.
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead>
                    <tr>
                        <th>Γραμμή</th>
                        <th>ΑΜ</th>
                        <th>Τηλέφωνο</th>
                        <th>Email</th>
                        <th>Επόπτης καθηγητής</th>
                        <th>Αποτέλεσμα</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="{{ $row['valid'] ? 'table-success' : 'table-danger' }}">
                            <td>{{ $row['line'] }}</td>
                            <td>{{ $row['amf'] ?: '—' }}</td>
                            <td>{{ $row['telephone'] ?: '—' }}</td>
                            <td>{{ $row['email'] ?: '—' }}</td>
                            <td>{{ $row['supervisor'] ?: '—' }}</td>
                            <td>
                                @if ($row['valid'])
                                    <span class="fw-semibold">Έτοιμη</span>
                                @else
                                    <ul class="mb-0 ps-3">
                                        @foreach ($row['errors'] as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($rows->isNotEmpty() && $validCount === $rows->count())
            <form method="POST" action="{{ route('students.import.store') }}" class="mt-3" onsubmit="return confirm('Να εισαχθούν οριστικά οι {{ $validCount }} φοιτητές;')">
                @csrf
                <input type="hidden" name="department_id" value="{{ $selectedDepartmentId }}">
                <input type="hidden" name="phase_id" value="{{ $selectedPhaseId }}">
                <textarea name="students" class="d-none" aria-hidden="true">{{ $studentsText }}</textarea>
                <button type="submit" class="btn btn-success">Οριστική εισαγωγή {{ $validCount }} φοιτητών</button>
            </form>
        @endif
    @endif

    @push('scripts')
        <script>
            const department = document.getElementById('department_id');
            const phase = document.getElementById('phase_id');

            if (department && phase) {
                const placeholder = phase.options[0].cloneNode(true);
                const options = Array.from(phase.querySelectorAll('option[data-department]'))
                    .map((option) => option.cloneNode(true));

                const filterPhases = () => {
                    const selectedPhase = phase.value;
                    const matchingOptions = options.filter((option) => option.dataset.department === department.value);

                    phase.replaceChildren(placeholder.cloneNode(true));
                    matchingOptions.forEach((option) => phase.appendChild(option.cloneNode(true)));

                    phase.value = matchingOptions.some((option) => option.value === selectedPhase)
                        ? selectedPhase
                        : '';
                };

                department.addEventListener('change', filterPhases);
                filterPhases();
            }
        </script>
    @endpush
@endsection
