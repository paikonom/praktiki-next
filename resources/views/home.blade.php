@extends('layouts.app')

@section('title', 'Πρακτική Άσκηση φοιτητών ΕΚΠΑ')

@section('content')
    <section class="text-center mb-5">
        <h1 class="display-6 fw-semibold mb-3">Πρακτική Άσκηση των φοιτητών του ΕΚΠΑ</h1>
        <p class="lead text-body-secondary mb-0">
            Επιλέξτε την ιδιότητά σας για να συνδεθείτε στην πλατφόρμα.
        </p>
    </section>

    <div class="row g-4 justify-content-center">
        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <h2 class="h5">Φοιτητής / Φοιτήτρια</h2>
                    <p class="text-body-secondary flex-grow-1">Σύνδεση για τη συμπλήρωση και παρακολούθηση της πρακτικής.</p>
                    @auth('student')
                        <a class="btn btn-primary" href="{{ route('student.dashboard') }}">Είσοδος στην εφαρμογή</a>
                    @else
                        <a class="btn btn-outline-primary" href="{{ route('student.login') }}">Σύνδεση φοιτητή</a>
                    @endauth
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <h2 class="h5">Φοιτητής/τρια ΔΟΑΤΑΠ</h2>
                    <p class="text-body-secondary flex-grow-1">Είσοδος φοιτητών και φοιτητριών μέσω ΔΟΑΤΑΠ.</p>
                    <button class="btn btn-outline-primary" type="button" disabled>Σύνδεση ΔΟΑΤΑΠ</button>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <h2 class="h5">Φορέας Υποδοχής</h2>
                    <p class="text-body-secondary flex-grow-1">Πρόσβαση του φορέα στα στοιχεία και τις ενέργειες της πρακτικής.</p>
                    @auth('provider')
                        <a class="btn btn-primary" href="{{ route('provider.dashboard') }}">Είσοδος στην εφαρμογή</a>
                    @else
                        <a class="btn btn-outline-primary" href="{{ route('provider.login') }}">Σύνδεση φορέα</a>
                    @endauth
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-primary shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <h2 class="h5">Γραφείο Πρακτικής Άσκησης / ΕΠ/ΥΠ</h2>
                    <p class="text-body-secondary flex-grow-1">Σύνδεση χειριστών και επιστημονικών υπευθύνων.</p>
                    @auth('web')
                        <a class="btn btn-primary" href="{{ route('dashboard') }}">Είσοδος στην εφαρμογή</a>
                    @else
                        <div class="d-grid gap-2"><a class="btn btn-primary" href="{{ route('login') }}">Σύνδεση χειριστή</a><a class="btn btn-outline-primary" href="{{ route('scientific_supervisor.login') }}">Σύνδεση ΕΠ/ΥΠ</a></div>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5">
        <a class="btn btn-link" href="https://praktiki.uoa.gr/?page_id=6044" target="_blank" rel="noopener">Συχνές Ερωτήσεις</a>
    </div>
@endsection

@section('footer')
    <footer class="bg-primary text-white mt-auto py-4">
        <div class="container text-center">
            <img src="{{ asset('images/epsa-koinwnikh-synoxh-2021-2027.jpg') }}" class="img-fluid bg-white mb-3" width="900" alt="Πρόγραμμα ΕΣΠΑ 2021–2027">
            <div class="small">© {{ now()->year }} Πρακτική Άσκηση των φοιτητών του ΕΚΠΑ</div>
        </div>
    </footer>
@endsection
