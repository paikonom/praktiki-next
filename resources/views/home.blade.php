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
                     <div class="d-grid gap-2">
                        
                        <a class="btn btn-primary disabled" href="{{ route('student.login') }}">Σύνδεση φοιτητή (soon)</a>
                        <a class="btn btn-outline-primary" href="{{ route('student.test_login') }}">Σύνδεση Φοιτητή (χωρίς λογαριασμό UOA)</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <h2 class="h5">Φοιτητής/τρια ΔΟΑΤΑΠ</h2>
                    <p class="text-body-secondary flex-grow-1">Είσοδος φοιτητών και φοιτητριών μέσω ΔΟΑΤΑΠ.</p>
                    <button class="btn btn-outline-primary" type="button" disabled>Σύνδεση ΔΟΑΤΑΠ</button>
                </div>
            </div>
        </div> -->

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <h2 class="h5">Φορέας Υποδοχής</h2>
                    <p class="text-body-secondary flex-grow-1">Πρόσβαση του φορέα στα στοιχεία και τις ενέργειες της πρακτικής.</p>
                    <a class="btn btn-outline-primary" href="{{ route('provider.login') }}">Σύνδεση φορέα</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <h2 class="h5">Υπεύθυνοι Π.Τ</h2>
                    <p class="text-body-secondary flex-grow-1">Σύνδεση Υπευθύνων Πρακτικής Άσκησης Τμημάτων.</p>
                    <div class="d-grid gap-2">
                        <!-- <a class="btn btn-primary" href="{{ route('login') }}">Σύνδεση χειριστή</a> -->
                        <a class="btn btn-outline-primary" href="{{ route('scientific_supervisor.login') }}">Σύνδεση Υπευθύνων Π.Τ</a>
                    </div>
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
