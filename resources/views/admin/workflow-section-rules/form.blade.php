@extends('layouts.app')

@section('title', $workflowSectionRule->exists ? 'Επεξεργασία κανόνα' : 'Νέος κανόνας')

@section('content')
<h1 class="h3 mb-4">{{ $workflowSectionRule->exists ? 'Επεξεργασία κανόνα ενότητας' : 'Νέος κανόνας ενότητας' }}</h1>
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $workflowSectionRule->exists ? route('admin.workflow-section-rules.update', $workflowSectionRule) : route('admin.workflow-section-rules.store') }}" class="card shadow-sm">
    @csrf @if ($workflowSectionRule->exists) @method('PUT') @endif
    <div class="card-body"><div class="row g-3">
        <div class="col-md-3"><label class="form-label">Χρηματοδότηση</label><select class="form-select" name="funding_code">@foreach (['all' => 'Όλες', 'Espa' => 'ΕΣΠΑ', 'Institution' => 'Ίδρυμα', 'InternshipProvider' => 'Φορέας'] as $value => $label)<option value="{{ $value }}" @selected(old('funding_code', $workflowSectionRule->funding_code ?? 'all') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Ενότητα</label><select class="form-select" name="section">@foreach (['A' => 'Α', 'B' => 'Β', 'G' => 'Γ', 'D' => 'Δ'] as $value => $label)<option value="{{ $value }}" @selected(old('section', $workflowSectionRule->section) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Ρόλος</label><select class="form-select" name="role">@foreach (['student' => 'Φοιτητής', 'provider' => 'Φορέας', 'scientific_supervisor' => 'ΕΠ/ΥΠ', 'member' => 'Χειριστής'] as $value => $label)<option value="{{ $value }}" @selected(old('role', $workflowSectionRule->role) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Διαθέσιμη</label><select class="form-select" name="available_after">@foreach (['always' => 'Πάντα', 'internship_start' => 'Μετά την έναρξη', 'internship_end' => 'Μετά τη λήξη'] as $value => $label)<option value="{{ $value }}" @selected(old('available_after', $workflowSectionRule->available_after ?? 'always') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12"><div class="alert alert-info mb-0">Η ενότητα ολοκληρώνεται όταν ολοκληρωθούν όλες οι ενεργές απαιτούμενες ενέργειες της.</div></div>
        <div class="col-md-4 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="requires_previous_section" value="0"><input class="form-check-input" type="checkbox" name="requires_previous_section" value="1" id="previous" @checked(old('requires_previous_section', $workflowSectionRule->exists ? $workflowSectionRule->requires_previous_section : true))><label class="form-check-label" for="previous">Απαιτεί την προηγούμενη ενότητα</label></div></div>
    </div></div>
    <div class="card-footer d-flex justify-content-between"><div class="d-flex gap-2"><button class="btn btn-primary">Αποθήκευση</button><a class="btn btn-outline-secondary" href="{{ route('admin.workflow-section-rules.index') }}">Ακύρωση</a></div>@if ($workflowSectionRule->exists)<button class="btn btn-outline-danger" type="submit" form="delete-rule">Διαγραφή</button>@endif</div>
</form>
@if ($workflowSectionRule->exists)<form id="delete-rule" method="POST" action="{{ route('admin.workflow-section-rules.destroy', $workflowSectionRule) }}" onsubmit="return confirm('Να διαγραφεί ο κανόνας;')">@csrf @method('DELETE')</form>@endif
@endsection
