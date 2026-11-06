@extends('layouts.app')

@section('title', 'Τύποι εγγράφων')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Τύποι εγγράφων</h1><p class="text-muted mb-0">Κανόνες εμφάνισης και ανεβάσματος χωρίς αλλαγή κώδικα.</p></div>
    <a class="btn btn-primary" href="{{ route('admin.document-types.create') }}">Νέος τύπος</a>
</div>
@if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="card shadow-sm"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Σειρά</th><th>Τύπος</th><th>Χρηματοδοτήσεις</th><th>Ανέβασμα από</th><th>Κατάσταση</th><th></th></tr></thead>
    <tbody>
    @foreach ($types as $type)
        <tr>
            <td>{{ $type->sort_order }}</td>
            <td><div class="fw-semibold">{{ $type->title }}</div><code>{{ $type->code }}</code></td>
            <td>{{ $type->fundings->pluck('funding_code')->join(', ') }}</td>
            <td>{{ $type->roles->where('can_upload', true)->pluck('role')->join(', ') ?: '—' }}</td>
            <td><span class="badge {{ $type->active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $type->active ? 'Ενεργό' : 'Ανενεργό' }}</span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.document-types.edit', $type) }}" data-scroll-return>Επεξεργασία</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div></div>
@endsection
