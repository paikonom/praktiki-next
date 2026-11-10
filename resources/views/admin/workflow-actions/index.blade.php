@extends('layouts.app')

@section('title', 'Ενέργειες ροής')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Ενέργειες ροής</h1><p class="text-muted mb-0">Τι εμφανίζεται, ποιος είναι υπεύθυνος και από πού προκύπτει η ολοκλήρωση.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('admin.workflow-section-rules.index') }}">Κανόνες ενοτήτων</a><a class="btn btn-primary" href="{{ route('admin.workflow-actions.create') }}">Νέα ενέργεια</a></div>
</div>
@php
    $sections = ['A' => 'Α · Πριν την έναρξη', 'B' => 'Β · Κατά τη διάρκεια', 'G' => 'Γ · Μετά τη λήξη', 'D' => 'Δ · Ολοκλήρωση πρακτικής'];
    $fundings = ['all' => 'Όλες οι χρηματοδοτήσεις', 'Espa' => 'ΕΣΠΑ', 'Institution' => 'Ίδρυμα', 'InternshipProvider' => 'Φορέας'];
    $roles = config('praktiki_workflow.role_labels');
@endphp
<form method="GET" class="card card-body shadow-sm mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Αναζήτηση</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Τίτλος ή κωδικός"></div>
        <div class="col-md-2"><label class="form-label">Ενότητα</label><select class="form-select" name="section"><option value="">Όλες</option>@foreach ($sections as $value => $label)<option value="{{ $value }}" @selected(request('section') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Χρηματοδότηση</label><select class="form-select" name="funding_code"><option value="">Χωρίς φίλτρο</option>@foreach ($fundings as $value => $label)<option value="{{ $value }}" @selected(request('funding_code') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Υπεύθυνος</label><select class="form-select" name="responsible_role"><option value="">Όλοι</option>@foreach ($roles as $value => $label)<option value="{{ $value }}" @selected(request('responsible_role') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-1"><label class="form-label">Κατάσταση</label><select class="form-select" name="active"><option value="">Όλες</option><option value="1" @selected(request('active') === '1')>Ενεργές</option><option value="0" @selected(request('active') === '0')>Ανενεργές</option></select></div>
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary">Φιλτράρισμα</button><a class="btn btn-outline-secondary" href="{{ route('admin.workflow-actions.index') }}">Καθαρισμός</a></div>
    </div>
</form>
<div class="card shadow-sm"><div class="table-responsive">
<table class="table table-sm align-middle mb-0">
    <thead><tr><th>Ενότητα</th><th>Χρηματοδότηση</th><th>Σειρά</th><th>Ενέργεια</th><th>Υπεύθυνος</th><th>Πηγή</th><th>Ολοκληρώνεται με</th><th>Προϋποθέσεις</th><th></th></tr></thead>
    <tbody>
    @foreach ($actions as $action)
        <tr class="{{ $action->active ? '' : 'table-danger' }}">
            <td><span class="badge text-bg-secondary">{{ $sections[$action->section] }}</span></td>
            <td>{{ $fundings[$action->funding_code] }}</td><td>{{ $action->sort_order }}</td>
            <td><div class="fw-semibold">{{ $action->title }}</div><code>{{ $action->code }}</code></td>
            <td>{{ $roles[$action->responsible_role] }}</td><td><code>{{ $action->source_type }}:{{ $action->source_key }}</code></td>
            <td class="small">{{ collect($action->completed_statuses)->join(', ') ?: '—' }} @unless($action->counts_for_section)<span class="badge text-bg-secondary">δεν μετρά</span>@endunless</td>
            <td class="small">{{ collect($action->prerequisites)->join(', ') ?: '—' }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.workflow-actions.edit', $action) }}">Επεξεργασία</a></td>
        </tr>
    @endforeach
    @if ($actions->isEmpty())<tr><td colspan="9" class="text-center text-muted py-4">Δεν βρέθηκαν ενέργειες.</td></tr>@endif
    </tbody>
</table>
</div></div>
@endsection
