@extends('layouts.app')

@section('title', 'Νέα εφαρμογή Πρακτικής Άσκησης')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <span class="badge text-bg-success mb-3">Τοπικό περιβάλλον</span>

                    <h1 class="h2 mb-3">Η νέα εφαρμογή λειτουργεί</h1>

                    <p class="lead mb-4">
                        Ο βασικός σκελετός Laravel, η MySQL και το Bootstrap έχουν εγκατασταθεί επιτυχώς.
                    </p>

                    <hr>

                    <p class="text-body-secondary mb-0">
                        Επόμενο βήμα: σχεδιασμός της νέας δομής δεδομένων και σταδιακή μεταφορά
                        των λειτουργιών της υπάρχουσας εφαρμογής.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
