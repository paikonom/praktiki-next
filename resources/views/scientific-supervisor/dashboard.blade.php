@extends('layouts.app')

@section('title', 'Οι φοιτητές μου')

@section('content')
@php($labels = ['legacy' => ['Legacy', 'text-bg-dark'], 'before' => ['Πριν την έναρξη', 'text-bg-secondary'], 'during' => ['Κατά τη διάρκεια', 'text-bg-primary'], 'after' => ['Μετά τη λήξη', 'text-bg-warning'], 'completed' => ['Ολοκληρωμένη', 'text-bg-success'], 'canceled' => ['Ακυρωμένη', 'text-bg-danger']])
<div class="mb-4"><h1 class="h2 mb-1">Οι φοιτητές μου</h1><div class="text-body-secondary">{{ auth('scientific_supervisor')->user()->full_name }}</div></div>
<form method="GET" class="card border-0 shadow-sm mb-4"><div class="card-body"><div class="row g-3 align-items-end">
    <div class="col-md-2"><label class="form-label" for="year">Έτος</label><select class="form-select" id="year" name="year"><option value="all" @selected($year === 'all')>Όλα</option>@foreach($years as $item)<option value="{{ $item }}" @selected($year === $item)>{{ $item }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label" for="funding">Χρηματοδότηση</label><select class="form-select" id="funding" name="funding"><option value="all" @selected($funding === 'all')>Όλες</option>@foreach($fundings as $item)<option value="{{ $item->code }}" @selected($funding === $item->code)>{{ $item->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label" for="phase">Φάση</label><select class="form-select" id="phase" name="phase"><option value="">Όλες</option>@foreach($phaseNames as $item)<option value="{{ $item }}" @selected($phase === $item)>{{ $item }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label" for="status">Κατάσταση</label><select class="form-select" id="status" name="status"><option value="">Όλες</option>@foreach($labels as $value => $item)<option value="{{ $value }}" @selected($status === $value)>{{ $item[0] }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label" for="search">Αναζήτηση</label><input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="Ονοματεπώνυμο ή ΑΜ"></div>
    <div class="col-md-1 d-grid"><button class="btn btn-primary">Προβολή</button></div>
</div></div></form>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Φοιτητής/τρια</th><th>ΑΜ</th><th>Φάση</th><th>Έτος</th><th>Περίοδος</th><th>Κατάσταση</th><th></th></tr></thead>
    <tbody>@forelse($students as $student)
        @php($state = $states[$student->id])
        <tr>
            <td class="fw-semibold">{{ $student->full_name }}</td><td>{{ $student->AMF }}</td>
            <td>{{ $student->phase?->name ?: '—' }}</td>
            <td>{{ $student->praxh_year ?: '—' }}</td>
            <td>{{ $student->startDate?->format('d/m/Y') ?: '—' }} – {{ $student->endDate?->format('d/m/Y') ?: '—' }}</td>
            <td><span class="badge {{ $labels[$state][1] }}">{{ $labels[$state][0] }}</span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('scientific_supervisor.students.show', $student) }}">Καρτέλα</a></td>
        </tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">Δεν υπάρχουν φοιτητές για τα επιλεγμένα φίλτρα.</td></tr>@endforelse</tbody>
</table></div></div>
@endsection
