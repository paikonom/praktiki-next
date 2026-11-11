@extends('layouts.app')

@section('title', $praktiki ? 'Επεξεργασία πρακτικής' : 'Νέα πρακτική')

@section('content')
    @php
        $fields = config("praktiki_internship.cycle_fields.{$funding}", []);
    @endphp

    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <h1 class="h3 mb-0">{{ $praktiki ? 'Επεξεργασία πρακτικής' : 'Νέα πρακτική' }}</h1>
        <a href="{{ route('praktikes.index') }}" class="btn btn-outline-secondary">Επιστροφή</a>
    </div>

    @if (! $praktiki)
        <form method="GET" action="{{ route('praktikes.create') }}" class="card border-0 shadow-sm mb-4">
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-10">
                    <label for="funding-choice" class="form-label">Τύπος χρηματοδότησης</label>
                    <select id="funding-choice" name="funding" class="form-select">
                        @foreach ($fundings as $availableFunding)
                            <option value="{{ $availableFunding->code }}" @selected($availableFunding->code === $funding)>
                                {{ $availableFunding->name }}
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

    <form method="POST" action="{{ $praktiki ? route('praktikes.update', $praktiki->id) : route('praktikes.store') }}" class="card border-0 shadow-sm">
        @csrf
        @if ($praktiki)
            @method('PUT')
        @endif
        <input type="hidden" name="funding" value="{{ $funding }}">
        <input type="hidden" name="active" value="0">

        <div class="card-header bg-primary text-white p-3">
            <strong>{{ $praktiki->funding_name ?? $fundings->firstWhere('code', $funding)?->name }}</strong>
        </div>
        <div class="card-body p-4">

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="praxh_year" class="form-label">Έτος πράξης</label>
                    <input id="praxh_year" name="praxh_year" class="form-control @error('praxh_year') is-invalid @enderror" value="{{ old('praxh_year', $praktiki->praxh_year ?? '') }}" placeholder="2026-2027" required>
                    @error('praxh_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input id="active" name="active" class="form-check-input" type="checkbox" value="1" @checked((bool) old('active', $praktiki->active ?? true))>
                        <label for="active" class="form-check-label">Ενεργή πρακτική</label>
                    </div>
                </div>

                @foreach ($fields as $column => $definition)
                    @php($multiline = $definition['multiline'] ?? false)
                    <div class="{{ $multiline ? 'col-12' : 'col-md-6' }}">
                        <label for="{{ $column }}" class="form-label">{{ $definition['label'] }}</label>
                        @if ($multiline)
                            <textarea id="{{ $column }}" name="{{ $column }}" class="form-control @error($column) is-invalid @enderror" rows="3">{{ old($column, $praktiki?->details[$column] ?? '') }}</textarea>
                        @else
                            <input id="{{ $column }}" name="{{ $column }}" class="form-control @error($column) is-invalid @enderror" value="{{ old($column, $praktiki?->details[$column] ?? '') }}">
                        @endif
                        @error($column)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endforeach

                <div class="col-12">
                    <label for="notes" class="form-label">Εσωτερικές σημειώσεις</label>
                    <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $praktiki->notes ?? '') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="card-footer bg-white p-3 text-end">
            <button class="btn btn-primary" type="submit">Αποθήκευση</button>
        </div>
    </form>
@endsection
