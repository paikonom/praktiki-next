@extends('layouts.app')

@section('title', 'Κανόνες ενοτήτων')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Κανόνες ενοτήτων</h1><p class="text-muted mb-0">Πότε ανοίγει και πότε ολοκληρώνεται κάθε ενότητα για κάθε ρόλο.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('admin.workflow-actions.index') }}">Ενέργειες</a><a class="btn btn-primary" href="{{ route('admin.workflow-section-rules.create') }}">Νέος κανόνας</a></div>
</div>
@php
    $sections = ['A' => 'Α · Πριν την έναρξη', 'B' => 'Β · Κατά τη διάρκεια', 'G' => 'Γ · Μετά τη λήξη', 'D' => 'Δ · Ολοκλήρωση πρακτικής'];
    $fundings = ['all' => 'Όλες οι χρηματοδοτήσεις', 'Espa' => 'ΕΣΠΑ', 'Institution' => 'Ίδρυμα', 'InternshipProvider' => 'Φορέας'];
    $roles = config('praktiki_workflow.role_labels');
    $availability = ['always' => 'Πάντα', 'internship_start' => 'Μετά την έναρξη', 'internship_end' => 'Μετά τη λήξη'];
@endphp
<form method="GET" class="card card-body shadow-sm mb-3"><div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label">Ενότητα</label><select class="form-select" name="section"><option value="">Όλες</option>@foreach ($sections as $value => $label)<option value="{{ $value }}" @selected(request('section') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Χρηματοδότηση</label><select class="form-select" name="funding_code"><option value="">Χωρίς φίλτρο</option>@foreach ($fundings as $value => $label)<option value="{{ $value }}" @selected(request('funding_code') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Ρόλος</label><select class="form-select" name="role"><option value="">Όλοι</option>@foreach ($roles as $value => $label)<option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary">Φιλτράρισμα</button><a class="btn btn-outline-secondary" href="{{ route('admin.workflow-section-rules.index') }}">Καθαρισμός</a></div>
</div></form>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Ενότητα</th><th>Χρηματοδότηση</th><th>Ρόλος</th><th>Διαθέσιμη</th><th>Προηγούμενη ενότητα</th><th></th></tr></thead>
    <tbody>@foreach ($rules as $rule)<tr><td><span class="badge text-bg-secondary">{{ $sections[$rule->section] }}</span></td><td>{{ $fundings[$rule->funding_code] }}</td><td>{{ $roles[$rule->role] }}</td><td>{{ $availability[$rule->available_after] }}</td><td>{{ $rule->requires_previous_section ? 'Ναι' : 'Όχι' }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.workflow-section-rules.edit', $rule) }}">Επεξεργασία</a></td></tr>@endforeach
    @if ($rules->isEmpty())<tr><td colspan="6" class="text-center text-muted py-4">Δεν βρέθηκαν κανόνες.</td></tr>@endif</tbody>
</table></div></div>
@endsection
