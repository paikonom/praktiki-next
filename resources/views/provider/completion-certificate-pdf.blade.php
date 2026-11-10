<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>Βεβαίωση εκτέλεσης πρακτικής άσκησης</title>
    <style>
        @page { margin: 55px 50px {{ $funding === 'Espa' ? '105px' : '60px' }}; }
        body { font-family: DejaVu Serif, serif; color: #000; font-size: 13px; line-height: 1.45; }
        .city { text-align: right; margin: 0 0 28px; }
        h1 { font-size: 16px; line-height: 1.4; text-align: center; margin: 0 0 28px; }
        p { text-align: justify; margin: 0 0 8px; }
        .signature { border-top: 1px solid #777; margin-top: 28px; padding-top: 24px; text-align: center; page-break-inside: avoid; }
        .signature p { text-align: center; margin-bottom: 18px; }
        footer.print-date { position: fixed; right: 0; bottom: -42px; left: 0; border-top: 1px solid #777; padding-top: 3px; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
    </style>
</head>
<body>
@if ($funding === 'Espa')
    @include('pdf.partials.espa-footer')
@else
    <footer class="print-date"><strong>Ημερομηνία Εκτύπωσης</strong> {{ now()->format('d/m/Y') }}</footer>
@endif
@php
    $studentName = filled($student->full_name) ? mb_strtoupper($student->full_name) : null;
    $providerName = filled($provider?->f_epwnymia) ? mb_strtoupper($provider->f_epwnymia) : null;
@endphp

<p class="city">Αθήνα, {{ now()->format('d/m/Y') }}</p>
<h1>Βεβαίωση Ολοκλήρωσης Πρακτικής Άσκησης φοιτητή/τριας από τον/την Νόμιμο Εκπρόσωπο του Φορέα Απασχόλησης</h1>

<p>
    Βεβαιώνουμε ότι ο/η <x-pdf.value :value="$studentName" />
    φοιτητής/τρια του Τμήματος <x-pdf.value :value="$department?->tmhma" /> του Εθνικού και
    Καποδιστριακού Πανεπιστημίου Αθηνών, με Αριθμό Μητρώου Σχολής
    <x-pdf.value :value="$student->AMF" />, πραγματοποίησε Πρακτική Άσκηση στον φορέα απασχόλησης:
    <x-pdf.value :value="$providerName" /> κατά το χρονικό διάστημα από
    <x-pdf.value :value="$student->startDate?->format('Y-m-d')" /> έως
    <x-pdf.value :value="$student->endDate?->format('Y-m-d')" />.
    @if ($student->diakoptomeno)
        (Υλοποίηση Πρακτικής Άσκησης: από [<x-pdf.value :value="$student->startDate?->format('Y-m-d')" /> -
        <x-pdf.value :value="$student->endDate_DKPT_A?->format('Y-m-d')" />] και
        [<x-pdf.value :value="$student->startDate_DKPT_B?->format('Y-m-d')" /> -
        <x-pdf.value :value="$student->endDate?->format('Y-m-d')" />])
    @endif
</p>

@if ($funding === 'InternshipProvider')
    <p>Ο/Η φοιτητής/τρια ασφαλίστηκε για τον κίνδυνο ατυχήματος σύμφωνα με την παρ. 1 του άρθρου 10 του Ν. 2217/1994 (Α΄ 83).</p>
@elseif ($funding === 'Institution')
    <p>Με βάση την κείμενη νομοθεσία (Άρθρο 69 παρ. 7, 8 του Ν. 4957/22) ο/η φοιτητής/τρια δεν αποζημιώθηκε για την Πρακτική Άσκηση.</p>
    <p>Ο/Η φοιτητής/τρια ασφαλίστηκε από τον προϋπολογισμό του έργου 21977 που διαχειρίζεται ο ΕΛΚΕ ΕΚΠΑ.</p>
@endif

<div class="signature">
    <p><strong>Ο/Η Νόμιμος/η Εκπρόσωπος του φορέα απασχόλησης</strong></p>
    <p>&nbsp;</p>
    <p><x-pdf.value :value="$provider?->legalRepresentative()" /></p>
    <p>(Υπογραφή-Σφραγίδα)</p>
</div>
</body>
</html>
