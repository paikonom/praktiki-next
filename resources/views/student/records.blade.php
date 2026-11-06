@extends('layouts.portal', ['portalRole' => 'student', 'activePortalPage' => 'records'])

@section('title', 'Δελτία και ερωτηματολόγια')

@section('portal-content')
    <h1 class="h3 mb-2">Δελτία και ερωτηματολόγια</h1>
    <p class="text-body-secondary mb-4">Από εδώ μπορείτε να συμπληρώσετε ή να δείτε τα απογραφικά δελτία και το ερωτηματολόγιο της πρακτικής σας.</p>

    <div class="list-group shadow-sm">
        @forelse ($recordRows as $row)
            @include('student.partials.action', $row + [
                'currentRole' => 'student',
                'showActionControls' => true,
            ])
        @empty
            <div class="list-group-item py-4 text-body-secondary">Δεν υπάρχουν διαθέσιμα δελτία ή ερωτηματολόγια για αυτή την πρακτική.</div>
        @endforelse
    </div>
@endsection
