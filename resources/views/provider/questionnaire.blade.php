@extends('layouts.portal', ['portalRole' => 'provider', 'activePortalPage' => 'questionnaire'])

@section('title', 'Αξιολόγηση Φοιτητή/τριας Πρακτικής Άσκησης από τον/την αρμόδιο/α Επόπτη/τρια')

@section('portal-content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Αξιολόγηση Φοιτητή/τριας Πρακτικής Άσκησης από τον/την αρμόδιο/α Επόπτη/τρια</h1>
    </div>

    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @if ($completed)
        <div class="alert alert-success">Υποβλήθηκε οριστικά στις {{ $questionnaire->submitted_at->format('d/m/Y H:i') }}.</div>
        <div class="d-flex justify-content-end mb-4">
            <a class="btn btn-primary" href="{{ route('provider.questionnaire.pdf') }}" target="_blank">Προβολή PDF</a>
        </div>
    @elseif ($legacyCompleted)
        <div class="alert alert-warning">Το ερωτηματολόγιο έχει υποβληθεί στην παλιά εφαρμογή.</div>
    @endif

    @if (! $legacyCompleted && ! $completed)
        <form method="POST" action="{{ route('provider.questionnaire.store') }}">@csrf

        @foreach ($sections as $section)
            <section class="card shadow-sm mb-4">
                <div class="card-header"><h2 class="h5 mb-0">{{ $section['title'] }}</h2></div>
                <div class="card-body">
                    @foreach ($section['questions'] as $question)
                        @php($answer = old('answers.'.$question->code, $answers->get($question->code)))
                        <div class="mb-4">
                            <div class="fw-semibold mb-2">{{ $question->label }}</div>
                            @if ($question->type === 'radio')
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($question->options as $option)
                                        <div class="form-check">
                                            <input class="form-check-input @error('answers.'.$question->code) is-invalid @enderror" type="radio"
                                                   name="answers[{{ $question->code }}]" id="provider-question-{{ $question->code }}-{{ $loop->index }}"
                                                   value="{{ $option }}" @checked((string) $answer === (string) $option)>
                                            <label class="form-check-label" for="provider-question-{{ $question->code }}-{{ $loop->index }}">{{ $option }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('answers.'.$question->code)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @else
                                <textarea class="form-control @error('answers.'.$question->code) is-invalid @enderror" name="answers[{{ $question->code }}]" rows="6">{{ $answer }}</textarea>
                                <div class="form-text">Έως 2 παράγραφοι. Κρατήστε αντίγραφο πριν από την οριστική υποβολή.</div>
                                @error('answers.'.$question->code)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="alert alert-warning">Η υποβολή είναι οριστική και αφορά τον συγκεκριμένο φοιτητή ή τη συγκεκριμένη φοιτήτρια.</div>
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <button class="btn btn-outline-primary" type="submit" name="submission" value="draft">Προσωρινή αποθήκευση</button>
            <button class="btn btn-success" type="submit" name="submission" value="final">Οριστική υποβολή</button>
        </div>
        </form>
    @endif
@endsection
