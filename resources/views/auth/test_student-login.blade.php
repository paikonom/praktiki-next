@extends('layouts.app')

@section('title', 'Δοκιμαστική σύνδεση φοιτητή')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="alert alert-warning">
                Προσωρινή σύνδεση με αριθμό μητρώου και UUID μέχρι να ενεργοποιηθεί το CAS.
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-4">Σύνδεση φοιτητή</h1>

                    <form method="POST" action="{{ route('student.test_login') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label" for="AMF">Αριθμός Μητρώου Φοιτητή</label>
                            <input class="form-control @error('AMF') is-invalid @enderror" id="AMF" name="AMF"
                                   type="text" inputmode="numeric" value="{{ old('AMF') }}" required autofocus>
                             @error('AMF')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <label class="form-label" for="uuid">UUID</label>
                            <input class="form-control @error('uuid') is-invalid @enderror" id="uuid" name="uuid"
                                   type="text" value="{{ old('uuid') }}" required>
                            @error('uuid')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button class="btn btn-primary w-100" type="submit">Σύνδεση</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
