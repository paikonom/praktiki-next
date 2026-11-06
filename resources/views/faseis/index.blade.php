@extends('layouts.app')

@section('title', 'Φάσεις πρακτικής')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Φάσεις πρακτικής</h1>
            <p class="text-muted mb-0">Οι φάσεις των τμημάτων στα οποία έχεις πρόσβαση.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('faseis.create', ['year' => $year, 'funding' => $funding]) }}" class="btn btn-success">Νέα Φάση</a>
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
                            <option value="{{ $availableFunding->code }}" @selected($availableFunding->code === $funding)>{{ $availableFunding->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Προβολή</button>
                </div>
            </div>
        </div>
    </form>

    @forelse ($departments as $department)
        @php
            $departmentPhases = $phases->get($department->id, collect());
            $canManage = !auth()->user()->view_only && match ($funding) {
                'Institution' => $department->can_manage_institution,
                'InternshipProvider' => $department->can_manage_internship_provider,
                default => $department->can_manage_espa,
            };
        @endphp
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge text-bg-light me-2">{{ $department->atlasId }}</span>
                    <strong>{{ $department->tmhma }}</strong>
                    @if ($department->sxolh)
                        <span class="d-block small opacity-75 mt-1">{{ $department->sxolh }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span>Φοιτητές <span class="badge text-bg-light">—</span></span>
                    @if ($canManage)
                        <button class="btn btn-light btn-sm" type="button" disabled title="Θα προστεθεί αργότερα">
                            Επεξεργασία Τμήματος
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @forelse ($departmentPhases as $phase)
                    <div class="phase-card border rounded bg-body-tertiary p-2 mb-2">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge text-bg-secondary">{{ $phase->funding_code }}</span>
                                <span class="badge rounded-pill text-bg-success">{{ $phase->name }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <strong>Φοιτητές: —</strong>
                                @if ($canManage)
                                    <a href="{{ route('faseis.edit', $phase->id) }}" class="btn btn-sm btn-outline-success">
                                        Επεξεργασία Φάσης
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            @foreach (config('praktiki_internship.phase_urls') as $column => $definition)
                                @php
                                    $urls = array_values(array_filter(
                                        array_map('trim', explode('___', $phase->{$column} ?? '')),
                                        fn ($url) => filter_var($url, FILTER_VALIDATE_URL)
                                            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
                                    ));
                                @endphp
                                @forelse ($urls as $url)
                                    <a href="{{ $url }}" class="btn btn-sm btn-outline-primary me-2 mb-2" target="_blank" rel="noopener">{{ $definition['button_label'] }}</a>
                                @empty
                                    <span class="text-danger d-inline-block px-2 py-1">{{ $definition['button_label'] }}</span>
                                @endforelse
                            @endforeach
                        </div>

                        @php
                            $fields = collect(config("praktiki_internship.phase_fields.{$funding}", []))
                                ->map(fn ($definition) => $definition['label'])
                                ->all() + [
                                    'dates' => 'Κείμενο συμπλήρωσης Ημερομηνιών',
                                    'notes' => 'Σημειώσεις',
                                ];
                        @endphp
                        <div class="row g-3">
                            @foreach ($fields as $column => $label)
                                @php
                                    $value = in_array($column, ['dates', 'notes'], true)
                                        ? $phase->{$column}
                                        : ($phase->details[$column] ?? null);
                                @endphp
                                <div class="{{ in_array($column, ['dates', 'notes'], true) ? 'col-12' : 'col-md-4' }}">
                                    <div class="border rounded px-2 py-1 small bg-white">
                                        <span class="text-muted">{{ $label }}:</span>
                                        @if (trim((string) $value) !== '')
                                            <span class="fw-semibold">{{ $value }}</span>
                                        @else
                                            <span class="text-danger">empty</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="alert alert-warning mb-0">Δεν βρέθηκαν φάσεις για αυτό το τμήμα.</div>
                @endforelse
            </div>
        </section>
    @empty
        <div class="alert alert-warning">Δεν βρέθηκαν φάσεις για τα επιλεγμένα φίλτρα.</div>
    @endforelse
@endsection
