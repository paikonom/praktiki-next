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
@endphp

<header>
    <img src="{{ public_path('images/190_logo_UOA_BW_cntr_GR_sites.png') }}" alt="ΕΚΠΑ">
</header>

<footer>
    Ημερομηνία συμπλήρωσης: <x-pdf.value :value="$questionnaire->submitted_at?->format('d/m/Y H:i')" plain />
    · Σελίδα <span class="page-number"></span>
    @if ($funding === 'Espa')
        <img src="{{ public_path('images/epsa-koinwnikh-synoxh-2021-2027.jpg') }}" alt="ΕΣΠΑ">
    @endif
</footer>
<br>
<h1>Αξιολόγηση Συνολικής Εμπειρίας Πρακτικής Άσκησης από τον/τη Φοιτητή/τρια</h1>

<div class="details">
    <div class="detail"><strong>ΟΝΟΜΑΤΕΠΩΝΥΜΟ ΦΟΙΤΗΤΗ/ΤΡΙΑΣ:</strong> <x-pdf.value :value="$student->full_name" plain /></div>
    <div class="detail"><strong>Αριθμός Μητρώου Σχολής:</strong> <x-pdf.value :value="$student->AMF" plain /></div>
    <div class="detail"><strong>Τμήμα:</strong> <x-pdf.value :value="$student->department?->tmhma" plain /></div>
    <div class="detail"><strong>Επιστημονικά Υπεύθυνος/η:</strong> <x-pdf.value :value="$supervisor?->full_name" plain /></div>
    <div class="detail"><strong>Φορέας Απασχόλησης:</strong> <x-pdf.value :value="$provider?->f_epwnymia" plain /></div>
    <div class="detail"><strong>Επιβλέπων/ουσα στον χώρο άσκησης:</strong> <x-pdf.value :value="$provider?->providerSupervisor()" plain /></div>
    <div class="detail"><strong>Email Επιβλέποντος/ουσας:</strong> <x-pdf.value :value="$provider?->f_epopt_email" plain /></div>
    <div class="detail"><strong>ΔΙΑΣΤΗΜΑ ΠΟΥ ΠΡΑΓΜΑΤΟΠΟΙΗΘΗΚΕ Η ΠΡΑΚΤΙΚΗ ΑΣΚΗΣΗ:</strong>
        <x-pdf.value :value="$student->startDate?->format('d/m/Y')" plain /> έως <x-pdf.value :value="$student->endDate?->format('d/m/Y')" plain />
        @if ($student->diakoptomeno)
            (<x-pdf.value :value="$student->startDate?->format('d/m/Y')" plain />–<x-pdf.value :value="$student->endDate_DKPT_A?->format('d/m/Y')" plain />
            και <x-pdf.value :value="$student->startDate_DKPT_B?->format('d/m/Y')" plain />–<x-pdf.value :value="$student->endDate?->format('d/m/Y')" plain />)
        @endif
    </div>
</div>

@foreach ($sections as $section)
    <h2><x-pdf.value :value="$section['title'] ?? null" plain /></h2>
    @foreach ($section['questions'] as $question)
        <div class="question">
            <div class="label"><x-pdf.value :value="$question->label" plain /></div>
            @if ($question->type === 'radio')
                @if (filled($answers->get($question->code)))
                    <div class="options">
                        @foreach ($question->options as $option)
                            <span class="@if ((string) $answers->get($question->code) === (string) $option) selected @endif"><x-pdf.value :value="$option" plain /></span>@unless($loop->last) &nbsp; @endunless
                        @endforeach
                    </div>
                @else
                    <div class="answer"><x-pdf.value :value="null" plain /></div>
                @endif
            @else
                <div class="answer"><x-pdf.value :value="$answers->get($question->code)" plain /></div>
            @endif
        </div>
    @endforeach
@endforeach
<table class="signatures" style="width:100%; border-collapse:collapse;">
    <tr>
        <th style="text-align:center;">
            Ο/Η ΦΟΙΤΗΤΗΣ/ΤΡΙΑ
        </th>
    </tr>
    <tr>
        <td style="height:60px;"></td>
    </tr>
    <tr>
        <td style="text-align:center;">
            <x-pdf.value :value="$student->full_name" />
        </td>
    </tr>
    <tr>
        <td style="text-align:center;">
            (Υπογραφή)
        </td>
    </tr>
</table>

</body>
</html>
