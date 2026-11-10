@extends('layouts.app')

@section('title', $supervisor->exists ? 'Επεξεργασία Επιστημονικού Υπεύθυνου' : 'Νέος Επιστημονικός Υπεύθυνος')

@section('content')
    <h1 class="h3 mb-1">{{ $supervisor->exists ? 'Επεξεργασία Επιστημονικού Υπεύθυνου' : 'Νέος Επιστημονικός Υπεύθυνος' }}</h1>
    <p class="text-muted mb-4">
        {{ $supervisor->exists
            ? 'Η επεξεργασία δεν αλλάζει το τμήμα, την ενεργή κατάσταση ή τις αναθέσεις φοιτητών.'
            : 'Με την αποθήκευση θα γίνει ενεργός και ο προηγούμενος υπεύθυνος του τμήματος θα παραμείνει στο ιστορικό ως ανενεργός.' }}
    </p>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $supervisor->exists ? route('admin.scientific-supervisors.update', $supervisor) : route('admin.scientific-supervisors.store') }}" class="card border-0 shadow-sm">
        @csrf
        @if ($supervisor->exists) @method('PUT') @endif
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-12">
                    <label for="department_id" class="form-label">Τμήμα</label>
                    <select id="department_id" name="department_id" class="form-select" required @disabled($supervisor->exists)>
                        <option value="">Επιλέξτε τμήμα</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) old('department_id', $supervisor->department_id) === $department->id)>{{ $department->atlasId }} - {{ $department->tmhma }}</option>
                        @endforeach
                    </select>
                    @if ($supervisor->exists)<input type="hidden" name="department_id" value="{{ $supervisor->department_id }}">@endif
                </div>
                <div class="col-md-6"><label for="fName" class="form-label">Όνομα</label><input id="fName" name="fName" class="form-control" value="{{ old('fName', $supervisor->fName) }}" required></div>
                <div class="col-md-6"><label for="lName" class="form-label">Επώνυμο</label><input id="lName" name="lName" class="form-control" value="{{ old('lName', $supervisor->lName) }}" required></div>
                <div class="col-md-6"><label for="email" class="form-label">Email</label><input id="email" name="email" type="email" class="form-control" value="{{ old('email', $supervisor->email) }}" required></div>
                <div class="col-md-6"><label for="thl" class="form-label">Τηλέφωνο</label><input id="thl" name="thl" class="form-control" value="{{ old('thl', $supervisor->thl) }}"></div>
                <div class="col-md-6"><label for="apofash_tmhmatos" class="form-label">Απόφαση τμήματος</label><input id="apofash_tmhmatos" name="apofash_tmhmatos" class="form-control" value="{{ old('apofash_tmhmatos', $supervisor->apofash_tmhmatos) }}"></div>
                <div class="col-md-6"><label for="syneleush_tmhmatos" class="form-label">Συνέλευση τμήματος</label><input id="syneleush_tmhmatos" name="syneleush_tmhmatos" class="form-control" value="{{ old('syneleush_tmhmatos', $supervisor->syneleush_tmhmatos) }}"></div>
                @if ($supervisor->exists)
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="active" value="0">
                            <input class="form-check-input" type="checkbox" name="active" value="1" id="active" @checked(old('active', $supervisor->active))>
                            <label class="form-check-label" for="active">Ενεργός για τις επόμενες εισαγωγές φοιτητών</label>
                        </div>
                        <div class="form-text">Η ενεργοποίηση απενεργοποιεί αυτόματα οποιονδήποτε άλλο ενεργό Υπεύθυνο/η Π.Τ. του ίδιου τμήματος.</div>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-footer bg-white p-4 d-flex gap-2">
            <button class="btn btn-primary">{{ $supervisor->exists ? 'Αποθήκευση αλλαγών' : 'Αποθήκευση και ενεργοποίηση' }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.scientific-supervisors.index') }}">Ακύρωση</a>
        </div>
    </form>
@endsection
