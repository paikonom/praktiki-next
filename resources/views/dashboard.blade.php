{{--@dd($departments)--}}

@extends('layouts.app')

@section('title', 'Πίνακας χειριστή')

@section('content')
    <section class="bg-primary text-white rounded-4 p-4 p-lg-5 mb-4 shadow-sm">
        <div class="d-lg-flex justify-content-between align-items-center gap-4">
            <div>
                <div class="small text-uppercase opacity-75 fw-semibold mb-2">Πίνακας χειριστή</div>
                <h1 class="display-6 fw-bold mb-2">Καλώς ήρθες, {{ auth()->user()->name }}</h1>
                <p class="mb-0 opacity-75">Τα τμήματα και οι πρακτικές που μπορείς να διαχειριστείς.</p>
            </div>
            <div class="mt-4 mt-lg-0 text-lg-end">
                <div class="small opacity-75">Ρόλος</div>
                <span class="badge bg-white text-primary fs-6">{{ auth()->user()->role }}</span>
            </div>
        </div>
    </section>
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Στοιχεία χρήστη</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted fw-normal">Όνομα</dt>
                        <dd class="col-sm-8 fw-semibold">{{ auth()->user()->name }}</dd>
                        <dt class="col-sm-4 text-muted fw-normal">Username</dt>
                        <dd class="col-sm-8">{{ auth()->user()->username }}</dd>
                        <dt class="col-sm-4 text-muted fw-normal">Email</dt>
                        <dd class="col-sm-8">{{ auth()->user()->email }}</dd>
                        <dt class="col-sm-4 text-muted fw-normal">User ID</dt>
                        <dd class="col-sm-8 mb-0">{{ auth()->id() }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Πρόσβαση και διαχείριση</h2>
                        @can('view-admin')
                            <a href="{{ route('admin.department-access') }}" class="btn btn-sm btn-outline-primary">Διαχείριση χρηστών</a>
                        @endcan
                    </div>
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md"><div class="border rounded-3 p-3"><div class="fs-4 fw-bold">{{ $counts['access'] }}</div><div class="small text-muted">Πρόσβαση</div></div></div>
                        <div class="col-6 col-md"><div class="border rounded-3 p-3"><div class="fs-4 fw-bold text-success">{{ $counts['managed'] }}</div><div class="small text-muted">Διαχείριση</div></div></div>
                        <div class="col-4 col-md"><div class="border rounded-3 p-3"><div class="fs-4 fw-bold text-primary">{{ $counts['espa'] }}</div><div class="small text-muted">ΕΣΠΑ</div></div></div>
                        <div class="col-4 col-md"><div class="border rounded-3 p-3"><div class="fs-4 fw-bold">{{ $counts['institution'] }}</div><div class="small text-muted">Ίδρυμα</div></div></div>
                        <div class="col-4 col-md"><div class="border rounded-3 p-3"><div class="fs-4 fw-bold text-dark">{{ $counts['provider'] }}</div><div class="small text-muted">Φορέας</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Τα τμήματά σου</h2>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('praktikes.index') }}" class="btn btn-sm btn-outline-primary">Στοιχεία πρακτικών</a>
            <a href="{{ route('faseis.index') }}" class="btn btn-sm btn-primary">Προβολή φάσεων</a>
            <span class="badge text-bg-dark rounded-pill">{{ $departments->count() }}</span>
        </div>
    </div>

    @forelse ($departments->chunk(3) as $departmentRow)
        <div class="row g-4 mb-4">
            @foreach ($departmentRow as $department)
                <div class="col-lg-4">
                    <article class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <span class="badge text-bg-dark">atlasId: {{ $department->atlasId }}</span>
                                <span class="badge rounded-pill {{ $department->active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $department->active ? 'Ενεργό' : 'Ανενεργό' }}
                                </span>
                            </div>
                            <h3 class="h6 fw-bold">{{ $department->tmhma }}</h3>
                            @if ($department->sxolh)
                                <p class="small text-muted mb-3">{{ $department->sxolh }}</p>
                            @endif
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge {{ $department->can_manage_espa ? 'text-bg-primary' : 'text-bg-light border text-muted' }}">ΕΣΠΑ {{ $department->can_manage_espa ? '✓' : 'μόνο προβολή' }}</span>
                                <span class="badge {{ $department->can_manage_institution ? 'text-bg-secondary' : 'text-bg-light border text-muted' }}">Ίδρυμα {{ $department->can_manage_institution ? '✓' : 'μόνο προβολή' }}</span>
                                <span class="badge {{ $department->can_manage_internship_provider ? 'text-bg-dark' : 'text-bg-light border text-muted' }}">Φορέας {{ $department->can_manage_internship_provider ? '✓' : 'μόνο προβολή' }}</span>
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    @empty
        <div class="alert alert-warning">Δεν βρέθηκαν τμήματα για τον λογαριασμό σου.</div>
    @endforelse
@endsection
