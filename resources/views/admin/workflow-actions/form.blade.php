@extends('layouts.app')

@section('title', $workflowAction->exists ? 'Επεξεργασία ενέργειας' : 'Νέα ενέργεια')

@section('content')
@php($selectedPrerequisites = old('prerequisites', $workflowAction->prerequisites ?? []))
@php($selectedAllowedStatuses = old('allowed_statuses', $workflowAction->allowed_statuses ?? ['pending', 'approved', 'rejected']))
@php($selectedCompletedStatuses = old('completed_statuses', $workflowAction->completed_statuses ?? ['approved']))
<h1 class="h3 mb-4">{{ $workflowAction->exists ? 'Επεξεργασία ενέργειας' : 'Νέα ενέργεια' }}</h1>
<form method="POST" action="{{ $workflowAction->exists ? route('admin.workflow-actions.update', $workflowAction) : route('admin.workflow-actions.store') }}" class="card shadow-sm">
    @csrf @if ($workflowAction->exists) @method('PUT') @endif
    <div class="card-body"><div class="row g-3">
        <div class="col-md-3"><label class="form-label">Χρηματοδότηση</label><select class="form-select" name="funding_code">@foreach (['all' => 'Όλες', 'Espa' => 'ΕΣΠΑ', 'Institution' => 'Ίδρυμα', 'InternshipProvider' => 'Φορέας'] as $value => $label)<option value="{{ $value }}" @selected(old('funding_code', $workflowAction->funding_code ?? 'all') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Ενότητα</label><select class="form-select" name="section">@foreach (['A' => 'Α', 'B' => 'Β', 'G' => 'Γ', 'D' => 'Δ'] as $value => $label)<option value="{{ $value }}" @selected(old('section', $workflowAction->section) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Σειρά</label><input class="form-control" type="number" step="0.01" min="0" name="sort_order" value="{{ old('sort_order', $workflowAction->sort_order ?? 0) }}" required></div>
        <div class="col-md-5"><label class="form-label">Κωδικός</label><input class="form-control" name="code" value="{{ old('code', $workflowAction->code) }}" required><div class="form-text">Το μοναδικό όνομα της ενέργειας στη ροή. Χρησιμοποιείται στις προϋποθέσεις και στους κανόνες· μην το αλλάζεις χωρίς λόγο.</div></div>
        <div class="col-12"><label class="form-label">Τίτλος</label><input class="form-control" name="title" value="{{ old('title', $workflowAction->title) }}" required></div>
        <div class="col-md-4"><label class="form-label">Υπεύθυνος</label><select class="form-select" name="responsible_role">@foreach (config('praktiki_workflow.role_labels') as $value => $label)<option value="{{ $value }}" @selected(old('responsible_role', $workflowAction->responsible_role) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12">
            <label class="form-label">Προϋποθέσεις</label>
            <div class="border rounded p-3">
                <div class="row g-2">
                    @forelse ($prerequisiteActions as $prerequisite)
                        <div class="col-lg-4 col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="prerequisites[]" value="{{ $prerequisite->code }}" id="prerequisite-{{ $prerequisite->id }}" @checked(in_array($prerequisite->code, $selectedPrerequisites, true))>
                                <label class="form-check-label" for="prerequisite-{{ $prerequisite->id }}">
                                    <span class="fw-semibold">{{ ['A' => 'Α', 'B' => 'Β', 'G' => 'Γ', 'D' => 'Δ'][$prerequisite->section] }}{{ $prerequisite->sort_order }}</span>
                                    {{ $prerequisite->title }}
                                    <code class="d-block">{{ $prerequisite->code }}</code>
                                </label>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">Δεν υπάρχουν άλλες ενέργειες.</div>
                    @endforelse
                </div>
            </div>
            <div class="form-text">Επίλεξε μόνο τις ενέργειες που πρέπει να έχουν ολοκληρωθεί πρώτα. Καμία επιλογή σημαίνει «χωρίς προϋπόθεση».</div>
        </div>
        <div class="col-md-5"><label class="form-label">Τύπος πηγής</label><select class="form-select" name="source_type">@foreach (['action', 'document', 'apografiko', 'student_questionnaire', 'provider_questionnaire'] as $value)<option @selected(old('source_type', $workflowAction->source_type) === $value)>{{ $value }}</option>@endforeach</select><div class="form-text">Πού θα ελέγξει το σύστημα αν ολοκληρώθηκε η ενέργεια: κατάσταση, αρχείο, απογραφικό ή ερωτηματολόγιο.</div></div>
        <div class="col-md-7"><label class="form-label">Κλειδί πηγής</label><input class="form-control" name="source_key" value="{{ old('source_key', $workflowAction->source_key) }}" required><div class="form-text">Τι ακριβώς θα αναζητήσει μέσα στην πηγή. Παράδειγμα: πηγή <code>document</code> και κλειδί <code>symf_initial</code>.</div></div>
        <div class="col-12">
            <label class="form-label">Επιτρεπτές και ολοκληρωμένες καταστάσεις</label>
            <div class="border rounded p-3 d-flex flex-wrap gap-4">
                @foreach (['pending', 'draft', 'submitted', 'processing', 'approved', 'completed', 'active', 'rejected', 'failed', 'cancelled', 'not_required'] as $workflowStatus)
                    <div>
                        <label class="form-check"><input class="form-check-input" type="checkbox" name="allowed_statuses[]" value="{{ $workflowStatus }}" @checked(in_array($workflowStatus, $selectedAllowedStatuses, true))> {{ $workflowStatus }}</label>
                        <label class="form-check small text-success"><input class="form-check-input" type="checkbox" name="completed_statuses[]" value="{{ $workflowStatus }}" @checked(in_array($workflowStatus, $selectedCompletedStatuses, true))> ολοκληρώνει</label>
                    </div>
                @endforeach
            </div>
            <div class="form-text">Μια ενέργεια ολοκληρώνει την ενότητά της μόνο όταν έχει μία από τις πράσινες καταστάσεις.</div>
        </div>
        <div class="col-md-3 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="counts_for_section" value="0"><input class="form-check-input" type="checkbox" name="counts_for_section" value="1" id="counts_for_section" @checked(old('counts_for_section', $workflowAction->exists ? $workflowAction->counts_for_section : true))><label class="form-check-label" for="counts_for_section">Μετρά στην ολοκλήρωση ενότητας</label></div></div>
        <div class="col-md-3 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="active" value="0"><input class="form-check-input" type="checkbox" name="active" value="1" id="active" @checked(old('active', $workflowAction->exists ? $workflowAction->active : true))><label class="form-check-label" for="active">Ενεργή</label></div></div>
    </div></div>
    <div class="card-footer d-flex justify-content-between"><div class="d-flex gap-2"><button class="btn btn-primary">Αποθήκευση</button><a class="btn btn-outline-secondary" href="{{ route('admin.workflow-actions.index') }}">Ακύρωση</a></div>@if ($workflowAction->exists)<button class="btn btn-outline-danger" type="submit" form="delete-action">Διαγραφή</button>@endif</div>
</form>
@if ($workflowAction->exists)<form id="delete-action" method="POST" action="{{ route('admin.workflow-actions.destroy', $workflowAction) }}" onsubmit="return confirm('Να διαγραφεί η ενέργεια;')">@csrf @method('DELETE')</form>@endif
@endsection
