@extends('layouts.app')

@section('title', 'Επεξεργασία πεδίου')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Επεξεργασία πεδίου</h1>
            <div class="text-body-secondary">Καρτέλα: {{ $studentCardField->card_section }} · <code>{{ $studentCardField->field }}</code></div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.student-card-fields.index') }}">Επιστροφή</a>
    </div>

    <form method="POST" action="{{ route('admin.student-card-fields.update', $studentCardField) }}" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body row g-3">
            <div class="col-md-8">
                <label class="form-label" for="label">Όνομα πεδίου</label>
                <input class="form-control @error('label') is-invalid @enderror" id="label" name="label" value="{{ old('label', $studentCardField->label) }}" required>
                @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="section">Ενότητα</label>
                <input class="form-control @error('section') is-invalid @enderror" id="section" name="section" value="{{ old('section', $studentCardField->section) }}">
                @error('section')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="help_text">Κείμενο οδηγίας</label>
                <textarea class="form-control @error('help_text') is-invalid @enderror" id="help_text" name="help_text" rows="3">{{ old('help_text', $studentCardField->help_text) }}</textarea>
                @error('help_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <fieldset>
                    <legend class="form-label">Χρηματοδότηση</legend>
                    @foreach (['all' => 'Όλες', 'Espa' => 'ΕΣΠΑ', 'InternshipProvider' => 'Φορέας', 'Institution' => 'Ίδρυμα'] as $value => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fundings[]" value="{{ $value }}" id="funding-{{ $value }}" @checked(in_array($value, old('fundings', $studentCardField->fundings ?? []), true))>
                            <label class="form-check-label" for="funding-{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach
                    @error('fundings')<div class="text-danger small">{{ $message }}</div>@enderror
                </fieldset>
            </div>
            <div class="col-md-6">
                <fieldset>
                    <legend class="form-label">Ποιος το βλέπει</legend>
                    @foreach (['all' => 'Όλοι', 'member' => 'Χειριστής', 'student' => 'Φοιτητής', 'provider' => 'Φορέας', 'scientific_supervisor' => 'ΕΠ/ΥΠ'] as $value => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="visible_to_roles[]" value="{{ $value }}" id="role-{{ $value }}" @checked(in_array($value, old('visible_to_roles', $studentCardField->visible_to_roles ?? []), true))>
                            <label class="form-check-label" for="role-{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach
                    @error('visible_to_roles')<div class="text-danger small">{{ $message }}</div>@enderror
                </fieldset>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="sort_order">Σειρά</label>
                <input class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $studentCardField->sort_order) }}" required>
                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="active">Κατάσταση</label>
                <select class="form-select" id="active" name="active">
                    <option value="1" @selected((int) old('active', $studentCardField->active) === 1)>Ενεργό</option>
                    <option value="0" @selected((int) old('active', $studentCardField->active) === 0)>Ανενεργό</option>
                </select>
            </div>
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-primary" type="submit">Αποθήκευση</button>
        </div>
    </form>
@endsection
