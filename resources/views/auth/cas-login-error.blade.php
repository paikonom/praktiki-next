@extends('layouts.app')

@section('title', 'Δεν βρέθηκε πρακτική άσκηση')

@section('content')
    <div class="alert alert-danger shadow-sm" role="alert">
        <h1 class="h4">Δεν ήταν δυνατή η σύνδεση</h1>
        <p class="mb-2">{{ $message }}</p>
        <p class="mb-0">Θα αποσυνδεθείτε αυτόματα από το UOA σε 5 δευτερόλεπτα.</p>
    </div>

    <a class="btn btn-outline-primary" href="{{ route('student.cas_logout') }}">Αποσύνδεση τώρα</a>
@endsection

@push('scripts')
    <script>
        window.setTimeout(() => {
            window.location.href = @json(route('student.cas_logout'));
        }, 5000);
    </script>
@endpush
