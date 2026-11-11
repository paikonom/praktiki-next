@extends('layouts.app')

@section('title', $phase ? 'Επεξεργασία φάσης' : 'Νέα φάση')

@section('content')
    @php
        $funding = $praktiki?->funding_code;
        $detailFields = config("praktiki_internship.phase_fields.{$funding}", []);
        $urlFields = config('praktiki_internship.phase_urls');
    @endphp

    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <h1 class="h3 mb-0">{{ $phase ? 'Επεξεργασία φάσης' : 'Νέα φάση' }}</h1>
        <a href="{{ route('faseis.index') }}" class="btn btn-outline-secondary">Επιστροφή</a>
    </div>

    @if (! $phase)
        <form method="GET" action="{{ route('faseis.create') }}" class="card border-0 shadow-sm mb-4">
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-10">
                    <label for="praktiki-cycle-choice" class="form-label">Πρακτική</label>
                    <select id="praktiki-cycle-choice" name="praktiki_cycle_id" class="form-select">
                        @foreach ($praktikes as $availablePraktiki)
                            <option value="{{ $availablePraktiki->id }}" @selected($availablePraktiki->id === $praktiki?->id)>
                                {{ $availablePraktiki->praxh_year }} — {{ $availablePraktiki->funding_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Επιλογή</button>
                </div>
            </div>
        </form>
    @endif

    @if ($praktiki)
        <form method="POST" action="{{ $phase ? route('faseis.update', $phase->id) : route('faseis.store') }}" class="card border-0 shadow-sm">
            @csrf
            @if ($phase)
                @method('PUT')
            @endif
            <input type="hidden" name="praktiki_cycle_id" value="{{ $phase ? $phase->praktiki_cycle_id : $praktiki->id }}">
            @if ($phase)
                <input type="hidden" name="department_id" value="{{ $phase->department_id }}">
            @endif

            <div class="card-header bg-primary text-white p-3">
                <strong>{{ $praktiki->praxh_year }} — {{ $praktiki->funding_name }}</strong>
                @if ($phase)
                    <span class="ms-2">{{ $phase->atlasId }} {{ $phase->tmhma }}</span>
                @endif
            </div>
            <div class="card-body p-4">

                @if (! $phase && $departments->isEmpty())
                    <div class="alert alert-warning mb-0">Δεν έχεις δικαίωμα διαχείρισης σε τμήμα για αυτή την πρακτική.</div>
                @else
                    <div class="row g-3">
                        @if (! $phase)
                            <div class="col-md-8">
                                <label for="department_id" class="form-label">Τμήμα</label>
                                <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((int) old('department_id') === $department->id)>
                                            {{ $department->atlasId }} — {{ $department->tmhma }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label for="name" class="form-label">Όνομα φάσης</label>
                            <select id="name" name="name" class="form-select @error('name') is-invalid @enderror" required>
                                <option value="">Επίλεξε φάση</option>
                                @foreach ($phaseNames as $phaseName)
                                    <option value="{{ $phaseName }}" @selected(old('name', $phase->name ?? '') === $phaseName)>{{ $phaseName }}</option>
                                @endforeach
                            </select>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @foreach ($detailFields as $column => $definition)
                            <div class="col-md-4">
                                <label for="{{ $column }}" class="form-label">{{ $definition['label'] }}</label>
                                <input id="{{ $column }}" name="{{ $column }}" class="form-control @error($column) is-invalid @enderror" value="{{ old($column, $phase?->details[$column] ?? '') }}">
                                @error($column)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach

                        <div class="col-12">
                            <label for="dates" class="form-label">Κείμενο συμπλήρωσης ημερομηνιών</label>
                            <textarea id="dates" name="dates" class="form-control @error('dates') is-invalid @enderror" rows="3">{{ old('dates', $phase->dates ?? '') }}</textarea>
                            @error('dates')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Σημειώσεις</label>
                            <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $phase->notes ?? '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @foreach ($urlFields as $column => $definition)
                            <div class="col-md-6">
                                <label for="{{ $column }}" class="form-label">{{ $definition['label'] }}</label>
                                <input id="{{ $column }}" name="{{ $column }}" class="form-control @error($column) is-invalid @enderror" value="{{ old($column, $phase->{$column} ?? '') }}">
                                @error($column)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @if ($phase || $departments->isNotEmpty())
                <div class="card-footer bg-white p-3 text-end">
                    <button class="btn btn-primary" type="submit">Αποθήκευση</button>
                </div>
            @endif
        </form>
    @else
        <div class="alert alert-warning">Δεν υπάρχει ενεργή πρακτική.</div>
    @endif
@endsection
