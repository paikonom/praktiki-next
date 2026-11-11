@extends('layouts.app')

@section('title', $documentType->exists ? 'Επεξεργασία τύπου εγγράφου' : 'Νέος τύπος εγγράφου')

@section('content')
@php
    $selectedFundings = old('fundings', $documentType->fundings?->pluck('funding_code')->all() ?? []);
    $selectedViews = old('view_roles', $documentType->roles?->where('can_view', true)->pluck('role')->all() ?? ['member']);
    $selectedUploads = old('upload_roles', $documentType->roles?->where('can_upload', true)->pluck('role')->all() ?? ['member']);
@endphp
<h1 class="h3 mb-4">{{ $documentType->exists ? 'Επεξεργασία τύπου εγγράφου' : 'Νέος τύπος εγγράφου' }}</h1>
<form method="POST" action="{{ $documentType->exists ? route('admin.document-types.update', $documentType) : route('admin.document-types.store') }}" class="card shadow-sm">
    @csrf @if ($documentType->exists) @method('PUT') @endif
    <div class="card-body"><div class="row g-3">
        <div class="col-md-4"><label class="form-label">Κωδικός</label><input class="form-control" name="code" value="{{ old('code', $documentType->code) }}" required></div>
        <div class="col-md-8"><label class="form-label">Τίτλος</label><input class="form-control" name="title" value="{{ old('title', $documentType->title) }}" required></div>
        <div class="col-12">
            <label class="form-label">Οδηγίες</label>
            <textarea class="form-control" name="instructions" rows="3">{{ old('instructions', $documentType->instructions) }}</textarea>
            <div class="form-text">Για σύνδεσμο γράψτε: [κείμενο συνδέσμου](https://example.gr/arxeio.pdf)</div>
        </div>
        <div class="col-md-4"><label class="form-label">Μέγιστο μέγεθος (MB)</label><input class="form-control" type="number" min="1" max="100" name="max_size_mb" value="{{ old('max_size_mb', $documentType->max_size_mb ?? 15) }}" required></div>
        <!-- <div class="col-md-4"><label class="form-label">Σειρά</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ old('sort_order', $documentType->sort_order ?? 0) }}" required></div> -->
        <div class="col-md-4">
    <label class="form-label">Σειρά</label>
    <input
        class="form-control"
        type="number"
        min="0"
        max="9999.99"
        step="0.01"
        name="sort_order"
        value="{{ old('sort_order', $documentType->sort_order ?? 0) }}"
        required
    >
</div>
        <div class="col-md-4"><div class="fw-semibold mb-2">Χρηματοδοτήσεις</div>@foreach (['Espa' => 'ΕΣΠΑ', 'Institution' => 'Ίδρυμα', 'InternshipProvider' => 'Φορέας'] as $value => $label)<div class="form-check"><input class="form-check-input" type="checkbox" name="fundings[]" value="{{ $value }}" id="f-{{ $value }}" @checked(in_array($value, $selectedFundings, true))><label class="form-check-label" for="f-{{ $value }}">{{ $label }}</label></div>@endforeach</div>

        <div class="col-md-4"><div class="fw-semibold mb-2">Ποιος βλέπει</div>@foreach (config('praktiki_workflow.role_labels') as $value => $label)<div class="form-check"><input class="form-check-input" type="checkbox" name="view_roles[]" value="{{ $value }}" id="v-{{ $value }}" @checked(in_array($value, $selectedViews, true))><label class="form-check-label" for="v-{{ $value }}">{{ $label }}</label></div>@endforeach</div>
        <div class="col-md-4"><div class="fw-semibold mb-2">Ποιος ανεβάζει</div>@foreach (config('praktiki_workflow.role_labels') as $value => $label)<div class="form-check"><input class="form-check-input" type="checkbox" name="upload_roles[]" value="{{ $value }}" id="u-{{ $value }}" @checked(in_array($value, $selectedUploads, true))><label class="form-check-label" for="u-{{ $value }}">{{ $label }}</label></div>@endforeach</div>
        <div class="col-12"><div class="form-check"><input type="hidden" name="active" value="0"><input class="form-check-input" type="checkbox" name="active" value="1" id="active" @checked(old('active', $documentType->exists ? $documentType->active : true))><label class="form-check-label" for="active">Ενεργό</label></div></div>
    </div></div>
    <div class="card-footer d-flex gap-2"><button class="btn btn-primary">Αποθήκευση</button><a class="btn btn-outline-secondary" href="{{ route('admin.document-types.index') }}">Ακύρωση</a></div>
</form>
@endsection
