@extends('layouts.app')

@section('title', 'Σύνδεση φορέα')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3">Σύνδεση φορέα υποδοχής</h1>
                    <p class="text-body-secondary">Ο κωδικός αφορά έναν συγκεκριμένο φοιτητή ή μία συγκεκριμένη φοιτήτρια.</p>
                    <form method="POST" action="{{ route('provider.login') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label" for="access_code">Μοναδικός κωδικός φοιτητή - φορέα</label>
                            <input class="form-control @error('access_code') is-invalid @enderror" id="access_code" name="access_code" type="password" required autofocus>
                            @error('access_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Σύνδεση</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
