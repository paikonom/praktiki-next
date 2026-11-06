@extends('layouts.app')

@section('title', 'Φοιτητές')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Φοιτητές</h1>
            <p class="text-muted mb-0">Οι φοιτητές των τμημάτων στα οποία έχεις πρόσβαση.</p>
        </div>
        <div class="d-flex gap-2">
            @if (! auth()->user()->view_only)
                <a href="{{ route('students.import.create') }}" class="btn btn-primary">Νέοι φοιτητές</a>
            @endif
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Επιστροφή</a>
        </div>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="year" class="form-label">Έτος πράξης</label>
                    <select id="year" name="year" class="form-select">
                        <option value="all" @selected($year === 'all')>Όλα τα έτη</option>
                        @foreach ($years as $availableYear)
                            <option value="{{ $availableYear }}" @selected($availableYear === $year)>{{ $availableYear }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="funding" class="form-label">Χρηματοδότηση</label>
                    <select id="funding" name="funding" class="form-select">
                        <option value="all" @selected($funding === 'all')>Όλες οι χρηματοδοτήσεις</option>
                        @foreach ($fundings as $availableFunding)
                            <option value="{{ $availableFunding->code }}" @selected($availableFunding->code === $funding)>
                                {{ $availableFunding->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="department_id" class="form-label">Τμήμα</label>
                    <select id="department_id" name="department_id" class="form-select">
                        <option value="">Όλα τα τμήματα</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected($department->id === $departmentId)>
                                {{ $department->atlasId }} - {{ $department->tmhma }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="phase" class="form-label">Φάση</label>
                    <select id="phase" name="phase" class="form-select">
                        <option value="">Όλες</option>
                        @foreach ($phaseNames as $availablePhase)
                            <option value="{{ $availablePhase }}" @selected($availablePhase === $phase)>{{ $availablePhase }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Προβολή</button>
                </div>
                <div class="col-md-10">
                    <label for="search" class="form-label">Αναζήτηση</label>
                    <input id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Ονοματεπώνυμο, ΑΜ, ΑΦΜ ή ΑΔΤ">
                </div>
                <div class="col-md-2">
                    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary w-100">Καθαρισμός</a>
                </div>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-end mb-3">
        <span class="badge text-bg-secondary">Σύνολο εγγραφών: {{ $studentCount }}</span>
    </div>

    @forelse ($studentGroups as $departmentStudents)
        @php
            $firstStudent = $departmentStudents->first();
        @endphp
        <section class="mb-4">
            <div class="alert alert-primary fw-bold mb-2">
                {{ $firstStudent->atlasId }}) {{ $firstStudent->tmhma }} — {{ $departmentStudents->count() }}
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Φοιτητής</th>
                            <th>Έναρξη - Λήξη</th>
                            <th>Εκκρεμότητες</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($departmentStudents as $student)
                            @php
                                $rowClass = match (true) {
                                    $student->isCancelled() => 'table-danger',
                                    $student->isCompleted() => 'table-success',
                                    $student->full_name === '' => 'table-warning',
                                    default => '',
                                };
                                $inputClass = match (true) {
                                    $student->funding_code !== 'Espa' => 'bg-secondary',
                                    $student->input_status === 'not_required' => 'bg-dark',
                                    $student->input_status === 'completed' => 'bg-success',
                                    $student->startDate?->isFuture() => 'bg-warning',
                                    default => 'bg-danger',
                                };
                                $outputClass = match (true) {
                                    $student->funding_code !== 'Espa' => 'bg-secondary',
                                    $student->output_status === 'not_required' => 'bg-dark',
                                    $student->output_status === 'completed' => 'bg-success',
                                    $student->endDate?->isFuture() => 'bg-warning',
                                    default => 'bg-danger',
                                };
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>{{ $student->id }}</td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        <span class="{{ in_array($student->stoix_student_status, ['submitted', 'approved', 'not_required'], true) ? 'bg-success' : 'bg-danger' }} bg-opacity-25 px-2 py-1 fw-semibold text-nowrap">ΦΤ</span>
                                        <span class="{{ in_array($student->stoix_forea_status, ['submitted', 'approved', 'not_required'], true) ? 'bg-success' : 'bg-danger' }} bg-opacity-25 px-2 py-1 fw-semibold text-nowrap">ΦΡ</span>
                                        <span class="{{ in_array($student->stoix_student_status, ['approved', 'not_required'], true) && in_array($student->stoix_forea_status, ['approved', 'not_required'], true) ? 'bg-success' : 'bg-danger' }} bg-opacity-25 px-2 py-1 fw-semibold text-nowrap">ΠΑ</span>
                                        <span class="{{ $inputClass }} bg-opacity-25 px-2 py-1 fw-semibold text-nowrap">←</span>
                                        <span class="{{ $outputClass }} bg-opacity-25 px-2 py-1 fw-semibold text-nowrap">→</span>
                                        <a class="btn btn-warning btn-sm" href="{{ route('students.show', $student->id) }}" title="Καρτέλα φοιτητή">C</a>
                                        <!-- <button class="btn btn-success btn-sm" type="button" disabled title="Τα αρχεία θα προστεθούν αργότερα">F</button> -->
                                        <!-- <a class="btn btn-primary btn-sm" href="{{ route('students.show', ['student' => $student->id, 'panel' => 'emails']) }}" title="Emails / Log">e</a> -->
                                        <button class="btn btn-info btn-sm" type="button" disabled title="Το Rescom θα προστεθεί αργότερα">R</button>
                                        <span class="badge rounded-pill text-bg-success ms-2">{{ $student->fashName ?: '—' }}</span>
                                        <span class="fw-bold">{{ $student->full_name ?: 'ΑΜΦ '.$student->AMF }}</span>
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    {{ $student->startDate?->format('d/m/Y') ?? '—' }}
                                    –
                                    {{ $student->endDate?->format('d/m/Y') ?? '—' }}
                                    @if ($student->duration_days !== null)
                                        <span class="text-muted">({{ $student->duration_days }})</span>
                                    @endif
                                </td>
                                <td>
                                    @php($nextAction = $nextActions[$student->id])
                                    <div class="small fw-semibold mb-1">{{ $nextAction['title'] }}</div>
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        @if (! empty($nextAction['email']))
                                            <x-email-send-button :student="$student" :email="$nextAction['email']" />
                                        @elseif (! empty($nextAction['code']))
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('students.show', $student) }}">Άνοιγμα</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="alert alert-warning">Δεν βρέθηκαν φοιτητές για τα επιλεγμένα φίλτρα.</div>
    @endforelse
@endsection
