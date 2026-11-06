@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $title }}</h1>
            <div class="text-body-secondary">{{ $student->full_name }} · Έκδοση {{ $questionnaire->version }}</div>
        </div>
        <div class="d-flex gap-2">
            @if ($questionnaire->isSubmitted())
                <a class="btn btn-primary" href="{{ route('students.questionnaires.pdf', [$student, $type]) }}" target="_blank">Προβολή PDF</a>
            @endif
            <a class="btn btn-outline-secondary" href="{{ route('students.show', ['student' => $student, 'panel' => 'questionnaires']) }}">Επιστροφή</a>
        </div>
    </div>

    <div class="alert alert-light border">
        <span class="badge {{ \App\Support\StatusBadge::className($questionnaire->status) }}">{{ $questionnaire->status }}</span>
        @if ($questionnaire->submitted_at)
            στις {{ $questionnaire->submitted_at->format('d/m/Y H:i') }}.
        @endif
    </div>

    @foreach ($sections as $section)
        <section class="card shadow-sm mb-4">
            <div class="card-header"><h2 class="h5 mb-0">{{ $section['title'] }}</h2></div>
            <div class="card-body">
                @foreach ($section['questions'] as $question)
                    @php($answer = $answers->get($question->code))
                    <div class="mb-4">
                        <div class="fw-semibold mb-2">{{ $question->label }}</div>
                        @if ($question->type === 'radio')
                            <div class="small fst-italic">
                                @foreach ($question->options as $option)
                                    @if ((string) $answer === (string) $option)<strong><u>{{ $option }}</u></strong>@else{{ $option }}@endif
                                    @unless ($loop->last), @endunless
                                @endforeach
                            </div>
                        @else
                            <div class="border rounded bg-body-tertiary p-3">{!! filled($answer) ? nl2br(e($answer)) : '—' !!}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
