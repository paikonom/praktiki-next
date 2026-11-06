<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>Ερωτηματολόγιο Φοιτητή</title>
    <style>
        @page { margin: 90px 42px 70px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        header { position: fixed; top: -72px; left: 0; right: 0; border-bottom: 1px solid #777; padding-bottom: 8px; }
        header img { width: 115px; vertical-align: middle; }
        footer { position: fixed; bottom: -52px; left: 0; right: 0; border-top: 1px solid #999; padding-top: 6px; font-size: 8px; }
        footer img { width: 330px; display: block; margin: 5px auto 0; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 16px; }
        h2 { font-size: 12px; margin: 18px 0 12px; }
        .details { border-bottom: 1px solid #777; padding-bottom: 9px; margin-bottom: 18px; }
        .detail { margin-bottom: 3px; }
        .detail strong { font-weight: bold; }
        .question { margin-bottom: 9px; page-break-inside: avoid; }
        .label { font-weight: bold; margin-bottom: 3px; }
        .answer { white-space: pre-wrap; }
        .options { margin-left: 20px; }
        .selected { font-weight: bold; text-decoration: underline; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
@php
    $supervisor = $student->supervisor;
    $provider = $student->provider;
    $period = ($student->startDate?->format('d/m/Y') ?? '—').' έως '.($student->endDate?->format('d/m/Y') ?? '—');
    if ($student->diakoptomeno && $student->endDate_DKPT_A && $student->startDate_DKPT_B) {
        $period .= ' ('.$student->startDate->format('d/m/Y').'–'.$student->endDate_DKPT_A->format('d/m/Y')
            .' και '.$student->startDate_DKPT_B->format('d/m/Y').'–'.$student->endDate->format('d/m/Y').')';
    }
@endphp

<header>
    <img src="{{ public_path('images/190_logo_UOA_BW_cntr_GR_sites.png') }}" alt="ΕΚΠΑ">
</header>

<footer>
    Ημερομηνία συμπλήρωσης: {{ $questionnaire->submitted_at->format('d/m/Y H:i') }}
    · Σελίδα <span class="page-number"></span>
    @if ($funding === 'Espa')
        <img src="{{ public_path('images/epsa-koinwnikh-synoxh-2021-2027.jpg') }}" alt="ΕΣΠΑ">
    @endif
</footer>

<h1>ΕΡΩΤΗΜΑΤΟΛΟΓΙΟ ΑΞΙΟΛΟΓΗΣΗΣ ΤΗΣ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ ΑΠΟ ΤΟΝ/ΤΗ ΦΟΙΤΗΤΗ/ΤΡΙΑ</h1>

<div class="details">
    <div class="detail"><strong>ΟΝΟΜΑΤΕΠΩΝΥΜΟ ΦΟΙΤΗΤΗ/ΤΡΙΑΣ:</strong> {{ trim($student->fName.' '.$student->lName) ?: '—' }}</div>
    <div class="detail"><strong>Αριθμός Μητρώου Σχολής:</strong> {{ $student->AMF ?: '—' }}</div>
    <div class="detail"><strong>Τμήμα:</strong> {{ $student->department?->tmhma ?: '—' }}</div>
    <div class="detail"><strong>Επιστημονικά Υπεύθυνος/η:</strong> {{ $supervisor ? trim($supervisor->fName.' '.$supervisor->lName) : '—' }}</div>
    <div class="detail"><strong>Φορέας Απασχόλησης:</strong> {{ $provider?->f_epwnymia ?: '—' }}</div>
    <div class="detail"><strong>Επιβλέπων/ουσα στον χώρο άσκησης:</strong> {{ $provider ? trim($provider->f_epopt_foreaFname.' '.$provider->f_epopt_foreaLname) ?: '—' : '—' }}</div>
    <div class="detail"><strong>Email Επιβλέποντος/ουσας:</strong> {{ $provider?->f_epopt_email ?: '—' }}</div>
    <div class="detail"><strong>ΔΙΑΣΤΗΜΑ ΠΟΥ ΠΡΑΓΜΑΤΟΠΟΙΗΘΗΚΕ Η ΠΡΑΚΤΙΚΗ ΑΣΚΗΣΗ:</strong> {{ $period }}</div>
</div>

@foreach ($sections as $section)
    <h2>{{ $section['title'] }}</h2>
    @foreach ($section['questions'] as $question)
        <div class="question">
            <div class="label">{{ $question->label }}</div>
            @if ($question->type === 'radio')
                <div class="options">
                    @foreach ($question->options as $option)
                        <span class="@if ((string) $answers->get($question->code) === (string) $option) selected @endif">{{ $option }}</span>@unless($loop->last) &nbsp; @endunless
                    @endforeach
                </div>
            @else
                <div class="answer">{{ filled($answers->get($question->code)) ? $answers->get($question->code) : '—' }}</div>
            @endif
        </div>
    @endforeach
@endforeach

</body>
</html>
