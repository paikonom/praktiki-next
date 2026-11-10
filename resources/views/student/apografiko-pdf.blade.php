<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>Απογραφικό δελτίο {{ $apografiko->type === 'input' ? 'εισόδου' : 'εξόδου' }}</title>
    <style>
        @page { margin: 105px 42px 72px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #172033; line-height: 1.35; }
        header { position: fixed; top: -96px; left: 0; right: 0; border-bottom: 2px solid #0d6efd; padding-bottom: 7px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-logo { width: 15%; text-align: center; vertical-align: middle; }
        .header-logo img { width: 100px; height: 70px; object-fit: contain; }
        .header-title { width: 70%; text-align: center; vertical-align: middle; font-size: 10px; font-weight: bold; line-height: 1.45; }
        footer { position: fixed; bottom: -61px; left: 0; right: 0; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-main { width: 70%; vertical-align: middle; }
        .footer-main img { width: 70%; opacity: 0.5; }
        .footer-dqs { width: 30%; text-align: right; vertical-align: middle; }
        .footer-dqs img { width: 20%; opacity: 0.5; }
        h1 { margin: 0 0 9px; text-align: center; font-size: 16px; color: #123b70; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 9px; }
        .summary td { width: 50%; padding: 4px 7px; border: 1px solid #d8e0ea; vertical-align: top; }
        .label { display: block; color: #64748b; font-size: 8px; }
        .value { font-weight: bold; }
        .ops { background: #eaf4ff; border-left: 4px solid #0d6efd; padding: 6px 9px; margin-bottom: 9px; }
        .questions { width: 100%; border-collapse: collapse; }
        .questions th { padding: 4px 7px; background: #eaf4ff; border: 1px solid #d8e0ea; color: #123b70; text-align: left; }
        .questions td { padding: 4px 7px; border: 1px solid #d8e0ea; vertical-align: top; }
        .questions tr { page-break-inside: avoid; }
        .question-code { font-weight: bold; color: #123b70; }
        .answer { width: 30px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
@php
    $isInput = $apografiko->type === 'input';
    $answerLabels = ['5321' => 'ΝΑΙ', '5322' => 'ΟΧΙ', '5323' => 'ΔΕΝ ΑΠΑΝΤΩ'];
@endphp
<header>
    <table class="header-table">
        <tr>
            <td class="header-logo"><img src="{{ public_path('images/greece.png') }}" alt="Ελληνική Δημοκρατία"></td>
            <td class="header-title">
                ΕΘΝΙΚΟ ΚΑΙ ΚΑΠΟΔΙΣΤΡΙΑΚΟ ΠΑΝΕΠΙΣΤΗΜΙΟ ΑΘΗΝΩΝ<br>
                Γραφείο Πρακτικής Άσκησης
            </td>
            <td class="header-logo"><img src="{{ public_path('images/eu.png') }}" alt="Ευρωπαϊκή Ένωση"></td>
        </tr>
    </table>
</header>
<footer>
    <table class="footer-table">
        <tr>
            <td class="footer-main"><img src="{{ public_path('images/epsa-koinwnikh-synoxh-2021-2027.jpg') }}" alt="ΕΣΠΑ 2021-2027"></td>
            <td class="footer-dqs"><img src="{{ public_path('images/dqs.png') }}" alt="DQS"></td>
        </tr>
    </table>
</footer>

<h1>Απογραφικό δελτίο {{ $isInput ? 'εισόδου' : 'εξόδου' }}</h1>

<table class="summary">
    <tr>
        <td><span class="label">Ονοματεπώνυμο</span><span class="value"><x-pdf.value :value="$student->full_name" plain /></span></td>
        <td><span class="label">Αριθμός Μητρώου</span><span class="value"><x-pdf.value :value="$student->AMF" plain /></span></td>
        <td><span class="label">ΑΦΜ</span><span class="value"><x-pdf.value :value="$student->afm" plain /></span></td>
    </tr>
    <tr><td><span class="label">Τμήμα</span><span class="value"><x-pdf.value :value="$student->department?->tmhma" plain /></span></td>
    <td><span class="label">Ημερομηνία δελτίου</span><span class="value"><x-pdf.value :value="($isInput ? $student->startDate : $student->endDate)?->format('d/m/Y')" plain /></span></td><td><span class="label">Ημερομηνία υποβολής</span><span class="value"><x-pdf.value :value="$apografiko->submitted_at?->format('d/m/Y H:i')" plain /></span></td></tr>
</table>

<div class="ops">
    <strong>Επιτυχής υποβολή στο ΟΠΣ</strong>
    MIS: <x-pdf.value :value="$mis" plain /> · ID δελτίου: <x-pdf.value :value="$apografiko->deltio_id" plain />
</div>

<table class="questions">
    <thead><tr><th>Ερώτηση</th><th class="answer">Απ.</th></tr></thead>
    <tbody>
    @foreach ($questions as $id => $question)
        @php($answer = $answerLabels[(string) ($apografiko->answers[$id] ?? '')] ?? null)
        <tr>
            <td><span class="question-code"><x-pdf.value :value="$question->kodikos_erotimatos" plain /></span> <x-pdf.value :value="$question->perigrafi" plain /></td>
            <td class="answer"><x-pdf.value :value="$answer" plain /></td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
