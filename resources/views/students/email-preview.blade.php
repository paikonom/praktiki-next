@extends('layouts.app')

@section('title', 'Προεπισκόπηση email')

@section('content')
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="h3 mb-0">Προεπισκόπηση email</h1>
                <span class="badge text-bg-primary fs-6">{{ $email['subject'] }}</span>
            </div>
            <div class="text-muted">{{ $student->full_name }}</div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('students.show', ['student' => $student, 'panel' => 'emails']) }}">Επιστροφή</a>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div><strong>Προς:</strong> {{ implode(', ', $email['to']) }}</div>
            @if ($email['cc'])<div><strong>Κοιν.:</strong> {{ implode(', ', $email['cc']) }}</div>@endif
            <div><strong>Θέμα:</strong> {{ $email['subject'] }}</div>
        </div>
        <div class="card-body">{!! $body !!}</div>
    </div>
@endsection
