@extends('layouts.app')

@section('title', 'Επιλογή πρακτικής άσκησης')

@section('content')
    @php
        $studentIdentity = $students->first();
    @endphp

    <div class="mb-4">
        <h1 class="h3 mb-2">Καλώς ήρθες, {{ $studentIdentity->full_name ?: 'φοιτητή/τρια' }}</h1>
        <div class="text-body-secondary">
            {{ $studentIdentity->department?->tmhma ?? 'Τμήμα χωρίς αντιστοίχιση' }}
            · Αριθμός Μητρώου: {{ $studentIdentity->AMF }}
        </div>
        <p class="mt-3 mb-0">Επίλεξε την πρακτική άσκηση που θέλεις να διαχειριστείς.</p>
    </div>

    <div class="row g-3">
        @foreach ($students as $student)
            @php
                $workflowState = $internshipStates[$student->id];
                $canceled = $workflowState === 'canceled';
                $stateLabel = [
                    'legacy' => 'Legacy',
                    'before' => 'Πριν την έναρξη',
                    'during' => 'Σε εξέλιξη',
                    'after' => 'Έχει λήξει',
                    'completed' => 'Ολοκληρωμένη',
                    'canceled' => 'Ακυρωμένη',
                ][$workflowState];
                $stateClass = [
                    'legacy' => 'text-bg-dark',
                    'before' => 'text-bg-secondary',
                    'during' => 'text-bg-primary',
                    'after' => 'text-bg-success',
                    'completed' => 'text-bg-success',
                    'canceled' => 'text-bg-danger',
                ][$workflowState];
                $borderClass = [
                    'legacy' => 'border-dark',
                    'before' => 'border-secondary',
                    'during' => 'border-primary',
                    'after' => 'border-success',
                    'completed' => 'border-success',
                    'canceled' => 'border-danger',
                ][$workflowState];
            @endphp
            <div class="col-md-6">
                <div class="card h-100 shadow-sm {{ $borderClass }}">
                    <div class="card-header d-flex justify-content-between align-items-center gap-2">
                        <span class="fw-semibold">Πρακτική {{ $student->praxh_year ?: '—' }}</span>
                        <span class="badge {{ $stateClass }}">{{ $stateLabel }}</span>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Χρηματοδότηση</dt>
                            <dd class="col-sm-7">{{ $student->fundingCode() ?: '—' }}</dd>

                            <dt class="col-sm-5">Φάση</dt>
                            <dd class="col-sm-7">{{ $student->phase?->name ?: '—' }}</dd>

                            <dt class="col-sm-5">Τμήμα</dt>
                            <dd class="col-sm-7">{{ $student->department?->tmhma ?: '—' }}</dd>

                            <dt class="col-sm-5">Ημερομηνίες</dt>
                            <dd class="col-sm-7">
                                {{ $student->startDate?->format('d/m/Y') ?? '—' }} – {{ $student->endDate?->format('d/m/Y') ?? '—' }}
                            </dd>

                            <dt class="col-sm-5">Διάρκεια</dt>
                            <dd class="col-sm-7">{{ $student->duration_days !== null ? $student->duration_days.' ημέρες' : '—' }}</dd>
                        </dl>
                    </div>
                    <div class="card-footer">
                        @if ($canceled)
                            <div class="text-danger mb-2">Η πρακτική έχει ακυρωθεί.</div>
                            <button class="btn btn-outline-danger w-100" type="button" disabled>Δεν επιτρέπεται σύνδεση</button>
                        @else
                            <form method="POST" action="{{ route('student.internships.select') }}">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                <button class="btn btn-primary w-100" type="submit">Επιλογή πρακτικής</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
