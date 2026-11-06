@extends('layouts.app')

@section('title', 'Σύνδεση χειριστή')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-4">Σύνδεση χειριστή</h1>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="username">Όνομα χρήστη</label>
                            <input
                                class="form-control @error('username') is-invalid @enderror"
                                id="username"
                                name="username"
                                type="text"
                                value="{{ old('username') }}"
                                autocomplete="username"
                                required
                                autofocus
                            >
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Κωδικός πρόσβασης</label>
                            <input
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                            <label class="form-check-label" for="remember">Να παραμείνω συνδεδεμένος</label>
                        </div>

                        <button class="btn btn-primary w-100" type="submit">Σύνδεση</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
