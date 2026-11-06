@extends('layouts.portal', ['portalRole' => 'student', 'activePortalPage' => 'contact'])

@section('title', 'Επικοινωνία')

@section('portal-content')
    <div class="mb-4"><h1 class="h3 mb-1">Επικοινωνία</h1><p class="text-body-secondary mb-0">Στοιχεία επικοινωνίας για την πρακτική σας.</p></div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h5">Τμήμα και επόπτης</h2>
                <dl class="mb-0">
                    <dt>Τμήμα</dt><dd>{{ $student->department?->tmhma ?? '—' }}</dd>
                    <dt>Επόπτης καθηγητής</dt><dd class="mb-0">{{ $student->epopt_kath ?: '—' }}</dd>
                </dl>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h5">Γραφείο Πρακτικής Άσκησης</h2>
                <p class="mb-2">Τα στοιχεία του αρμόδιου χειριστή θα εμφανίζονται όταν συνδεθούν με τα νέα δικαιώματα τμήματος.</p>
                <div class="alert alert-warning mb-0">Σε κάθε email να αναφέρετε το ΑΦΜ και τον αριθμό μητρώου σας.</div>
            </div></div>
        </div>
    </div>
@endsection
