@extends('layouts.app')

@section('title', 'Επιστημονικοί Υπεύθυνοι')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Επιστημονικοί Υπεύθυνοι</h1>
            <p class="text-muted mb-0">Ο ενεργός υπεύθυνος ανατίθεται αυτόματα μόνο στους νέους φοιτητές του τμήματος.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.scientific-supervisors.create') }}">Νέος ΕΠ/ΥΠ</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Τμήμα</th><th>ΕΠ/ΥΠ</th><th>Επικοινωνία</th><th>Κωδικός σύνδεσης</th><th>Φοιτητές</th><th>Κατάσταση</th><th></th></tr></thead>
                <tbody>
                    @forelse ($supervisors as $supervisor)
                        <tr>
                            <td>{{ $supervisor->department->atlasId }} - {{ $supervisor->department->tmhma }}</td>
                            <td class="fw-semibold">{{ $supervisor->full_name }}</td>
                            <td>{{ $supervisor->email }}@if ($supervisor->thl)<br><span class="text-muted">{{ $supervisor->thl }}</span>@endif</td>
                            <td><code>{{ $supervisor->access_code }}</code></td>
                            <td>{{ $supervisor->students_count }}</td>
                            <td><span class="badge {{ $supervisor->active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $supervisor->active ? 'Ενεργός' : 'Ανενεργός' }}</span></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.scientific-supervisors.edit', $supervisor) }}">Επεξεργασία</a>
                                    @unless ($supervisor->active)
                                        <form method="POST" action="{{ route('admin.scientific-supervisors.activate', $supervisor) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-primary">Ενεργοποίηση</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Δεν υπάρχουν Επιστημονικοί Υπεύθυνοι.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
