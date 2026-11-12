@extends('layouts.app')

@section('title', 'Φοιτητές')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <!-- <span class="h4 mb-1">Φοιτητές</span> -->
            <span class="badge text-bg-secondary">Σύνολο εγγραφών: {{ $studentCount }}</span>
            <!-- <p class="text-muted mb-0">Οι φοιτητές των τμημάτων στα οποία έχεις πρόσβαση.</p> -->
        </div>
        <div class="d-flex gap-2">
            @if (! auth()->user()->view_only)
                <a href="{{ route('students.import.create') }}" class="btn btn-primary">Νέοι φοιτητές</a>
            @endif
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Επιστροφή</a>
        </div>
    </div>
     <div class="d-flex justify-content-end mb-3">
        
    </div>

    @include('students.partials.filters')

   

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
@php
//dd($doc);
 //$downloadRoute = route('scientific_supervisor.documents.download', $document); 
 //$downloadRoute = route('scientific_supervisor.documents.download', $document);
 //<a class="btn btn-sm btn-outline-primary" href="{{ $downloadRoute }}">Λήψη</a>
@endphp
                            

                                <td>{{ $student->id }}</td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                        <span class="{{ in_array($student->stoix_student_status, ['submitted', 'approved', 'not_required'], true) ? 'bg-success' : 'bg-danger' }} bg-opacity-25 px-2 py-1 fw-semibold text-nowrap">ΦΤ</span>
                                        <span class="{{ in_array($student->stoix_forea_status, ['submitted', 'approved', 'not_required'], true) ? 'bg-success' : 'bg-danger' }} bg-opacity-25 px-2 py-1 fw-semibold text-nowrap">ΦΡ</span>
                                        <span class="{{ in_array($student->stoix_student_status, ['approved', 'not_required'], true) && in_array($student->stoix_forea_status, ['approved', 'not_required'], true) ? 'bg-success' : 'bg-danger' }} bg-opacity-25 px-2 py-1 fw-semibold text-nowrap">ΠΑ</span>
                                        <span class="{{ $inputClass }} bg-opacity-25 px-2 py-1 fw-semibold text-nowrap">←</span>
                                        <span class="{{ $outputClass }} bg-opacity-25 px-2 py-1 fw-semibold text-nowrap">→</span>
                                        <a class="btn btn-warning btn-sm" href="{{ route('students.show', $student->id) }}" title="Καρτέλα φοιτητή">C</a>
                                        <a class="btn btn-success btn-sm"
                                        href="{{ route('students.show', ['student' => $student->id, 'panel' => 'documents']) }}"
                                        title="Καρτέλα φοιτητή">F</a>
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
                                    @if (($nextAction['approval_count'] ?? 0) > 0)
                                        <div class="small fw-semibold text-danger mb-1">
                                            Έγκριση Εντύπων: <span class="badge text-bg-danger">{{ $nextAction['approval_count'] }}</span>
                                        </div>
                                    @endif
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        @forelse ($nextAction['emails'] ?? [] as $email)
                                            <x-email-send-button :student="$student" :email="$email" />
                                        @empty
                                            @if (! empty($nextAction['code']))
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('students.show', $student) }}">Άνοιγμα</a>
                                            @endif
                                        @endforelse
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
