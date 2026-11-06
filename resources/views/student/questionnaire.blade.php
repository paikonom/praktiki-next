@extends('layouts.portal', ['portalRole' => 'student', 'activePortalPage' => 'records'])

@section('title', 'Ερωτηματολόγιο αξιολόγησης')

@section('portal-content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Ερωτηματολόγιο αξιολόγησης της Πρακτικής Άσκησης</h1>
        <div class="text-body-secondary">Από τον/τη φοιτητή/τρια · Έκδοση {{ $version }}</div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-primary">
        <strong>Σκοπός:</strong> Η συλλογή απόψεων για την ποιότητα, την οργάνωση, την εκπαιδευτική αξία και την αποτελεσματικότητα της πρακτικής άσκησης.
    </div>

    @if ($completed)
        <div class="alert alert-success">
            Υποβλήθηκε οριστικά στις {{ $questionnaire->submitted_at->format('d/m/Y H:i') }}.
        </div>

        <div class="d-flex justify-content-end mb-4">
            <a class="btn btn-primary" href="{{ route('student.questionnaire.pdf') }}" target="_blank">
                Προβολή PDF
            </a>
        </div>

        @php
            $supervisor = $student->supervisor;
            $provider = $student->provider;
            $practicePeriod = $student->startDate?->format('d/m/Y').' έως '.$student->endDate?->format('d/m/Y');
            if ($student->diakoptomeno && $student->endDate_DKPT_A && $student->startDate_DKPT_B) {
                $practicePeriod .= ' ('.$student->startDate->format('d/m/Y').'–'.$student->endDate_DKPT_A->format('d/m/Y')
                    .' και '.$student->startDate_DKPT_B->format('d/m/Y').'–'.$student->endDate->format('d/m/Y').')';
            }
        @endphp
        <section class="card shadow-sm mb-4">
            <div class="card-header"><h2 class="h5 mb-0">Α. Γενικά Στοιχεία</h2></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-md-4">Ονοματεπώνυμο φοιτητή/τριας</dt>
                    <dd class="col-md-8">{{ $student->fName }} {{ $student->lName }}</dd>
                    <dt class="col-md-4">Αριθμός Μητρώου Σχολής</dt>
                    <dd class="col-md-8">{{ $student->AMF ?: '—' }}</dd>
                    <dt class="col-md-4">Τμήμα</dt>
                    <dd class="col-md-8">{{ $student->department?->tmhma ?: '—' }}</dd>
                    <dt class="col-md-4">Επιστημονικά Υπεύθυνος/η</dt>
                    <dd class="col-md-8">{{ $supervisor ? trim($supervisor->fName.' '.$supervisor->lName) : '—' }}</dd>
                    <dt class="col-md-4">Φορέας Απασχόλησης</dt>
                    <dd class="col-md-8">{{ $provider?->f_epwnymia ?: '—' }}</dd>
                    <dt class="col-md-4">Επόπτης/τρια στον χώρο άσκησης</dt>
                    <dd class="col-md-8">{{ $provider ? trim($provider->f_epopt_foreaFname.' '.$provider->f_epopt_foreaLname) ?: '—' : '—' }}</dd>
                    <dt class="col-md-4">Email Επόπτη/τριας</dt>
                    <dd class="col-md-8">{{ $provider?->f_epopt_email ?: '—' }}</dd>
                    <dt class="col-md-4">Διάστημα Πρακτικής Άσκησης</dt>
                    <dd class="col-md-8">{{ $practicePeriod }}</dd>
                </dl>
            </div>
        </section>
    @elseif ($legacyCompleted)
        <div class="alert alert-warning">
            Το ερωτηματολόγιο έχει υποβληθεί στην παλιά εφαρμογή. Η προβολή των παλιών απαντήσεων θα είναι διαθέσιμη μετά τη μεταφορά τους.
        </div>
    @endif

    @unless ($legacyCompleted)
      @unless ($completed)
        <form method="POST" action="{{ route('student.questionnaire.store') }}">
        @csrf
      @endunless

        @foreach ($sections as $section)
            <section class="card shadow-sm mb-4">
                <div class="card-header"><h2 class="h5 mb-0">{{ $section['title'] }}</h2></div>
                <div class="card-body">
                    @foreach ($section['questions'] as $question)
                        @php
                            $answer = old('answers.'.$question->code, $answers->get($question->code));
                        @endphp
                        <div class="mb-4">
                            <div class="fw-semibold mb-2">{{ $question->label }}</div>

                            @if ($completed)
                                @if ($question->type === 'radio')
                                    <div class="small fst-italic">
                                        @foreach ($question->options as $option)
                                            @if ((string) $answer === (string) $option)
                                                <strong><u>{{ $option }}</u></strong>
                                            @else
                                                {{ $option }}
                                            @endif
                                            @unless ($loop->last), @endunless
                                        @endforeach
                                    </div>
                                @else
                                    <div class="border rounded bg-body-tertiary p-3">
                                        {!! filled($answer) ? nl2br(e($answer)) : '<span class="text-body-secondary">—</span>' !!}
                                    </div>
                                @endif
                            @elseif ($question->type === 'radio')
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($question->options as $option)
                                        <div class="form-check">
                                            <input class="form-check-input @error('answers.'.$question->code) is-invalid @enderror"
                                                   type="radio" name="answers[{{ $question->code }}]"
                                                   id="question-{{ $question->code }}-{{ $loop->index }}" value="{{ $option }}"
                                                   @checked((string) $answer === (string) $option || ($answer === null && ($version === 2 ? $loop->last : $loop->first)))>
                                            <label class="form-check-label" for="question-{{ $question->code }}-{{ $loop->index }}">{{ $option }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('answers.'.$question->code)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @else
                                <textarea class="form-control @error('answers.'.$question->code) is-invalid @enderror"
                                          name="answers[{{ $question->code }}]" rows="6">{{ $answer }}</textarea>
                                <div class="form-text">Έως 2 παράγραφοι. Κρατήστε ένα αντίγραφο του κειμένου σας πριν από την οριστική υποβολή.</div>
                                @error('answers.'.$question->code)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        @unless ($completed)
            <div class="alert alert-warning">Η υποβολή είναι οριστική και δεν μπορεί να τροποποιηθεί από τον φοιτητή.</div>
            <div class="d-flex flex-wrap justify-content-end gap-2">
                <button class="btn btn-outline-primary" type="submit" name="submission" value="draft">Προσωρινή αποθήκευση</button>
                <button class="btn btn-success" type="submit" name="submission" value="final">Οριστική υποβολή</button>
            </div>
          </form>
        @endunless
    @endunless
@endsection
