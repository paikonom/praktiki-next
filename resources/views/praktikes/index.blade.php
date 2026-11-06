@extends('layouts.app')

@section('title', 'Στοιχεία πρακτικών')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Στοιχεία πρακτικών</h1>
            <p class="text-muted mb-0">Τα στοιχεία κάθε χρηματοδότησης ανά έτος πράξης.</p>
        </div>
        <div class="d-flex gap-2">
            @can('create', \App\Models\Praktiki::class)
                <a href="{{ route('praktikes.create') }}" class="btn btn-success">Νέα Πρακτική</a>
            @else
                <button class="btn btn-success" type="button" disabled title="Μόνο για διαχειριστές">Νέα Πρακτική</button>
            @endcan
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Επιστροφή</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="year" class="form-label">Έτος πράξης</label>
                    <select id="year" name="year" class="form-select">
                        @foreach ($years as $availableYear)
                            <option value="{{ $availableYear }}" @selected($availableYear === $year)>{{ $availableYear }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="funding" class="form-label">Χρηματοδότηση</label>
                    <select id="funding" name="funding" class="form-select">
                        @foreach ($fundings as $availableFunding)
                            <option value="{{ $availableFunding->code }}" @selected($availableFunding->code === $funding)>
                                {{ $availableFunding->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Προβολή</button>
                </div>
            </div>
        </div>
    </form>

    @if ($praktiki)
        @php
            $fields = config('praktiki_internship.cycle_fields.'.$praktiki->funding_code, []);
        @endphp

        <article class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center gap-3 p-3">
                <div>
                    <span class="badge text-bg-light me-2">{{ $praktiki->praxh_year }}</span>
                    <strong>{{ $praktiki->funding_name }}</strong>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ $praktiki->active ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $praktiki->active ? 'Ενεργή' : 'Ανενεργή' }}
                    </span>
                    @can('update', $praktiki)
                        <a href="{{ route('praktikes.edit', $praktiki->id) }}" class="btn btn-sm btn-light">Επεξεργασία</a>
                    @else
                        <button class="btn btn-sm btn-light" type="button" disabled title="Μόνο για διαχειριστές">Επεξεργασία</button>
                    @endcan
                </div>
            </div>
            <div class="card-body p-4">
                @if ($fields !== [])
                    <div class="row g-3">
                        @foreach ($fields as $column => $definition)
                            <div class="{{ ($definition['multiline'] ?? false) ? 'col-12' : 'col-md-6' }}">
                                <div class="border rounded p-3 h-100 bg-body-tertiary">
                                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                                        <span class="fw-semibold">{{ $definition['label'] }}:</span>
                                        @if (trim((string) ($praktiki->details[$column] ?? '')) !== '')
                                            <span class="text-muted">{{ $praktiki->details[$column] }}</span>
                                        @else
                                            <span class="text-danger">empty</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted">Η συγκεκριμένη χρηματοδότηση δεν χρειάζεται ειδικά στοιχεία κύκλου.</div>
                @endif
            </div>
        </article>
    @else
        <div class="alert alert-warning">Δεν υπάρχει πρακτική για τον επιλεγμένο συνδυασμό.</div>
    @endif
@endsection
