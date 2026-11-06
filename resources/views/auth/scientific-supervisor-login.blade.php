@extends('layouts.app')

@section('title', 'Σύνδεση ΕΠ/ΥΠ')

@section('content')
<div class="row justify-content-center"><div class="col-md-7 col-lg-5"><div class="card border-0 shadow-sm"><div class="card-body p-4">
    <h1 class="h3 mb-4">Σύνδεση ΕΠ/ΥΠ</h1>
    <form method="POST" action="{{ route('scientific_supervisor.login') }}">@csrf
        <label class="form-label" for="access_code">Μοναδικός κωδικός</label>
        <input class="form-control @error('access_code') is-invalid @enderror" id="access_code" name="access_code" value="{{ old('access_code') }}" required autofocus>
        @error('access_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <button class="btn btn-primary w-100 mt-4">Σύνδεση</button>
    </form>
</div></div></div></div>
@endsection
