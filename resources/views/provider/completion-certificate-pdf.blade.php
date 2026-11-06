<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>Βεβαίωση εκτέλεσης πρακτικής άσκησης</title>
    <style>
        @page { margin: 55px 50px {{ $funding === 'Espa' ? '105px' : '60px' }}; }
        body { font-family: DejaVu Serif, serif; color: #000; font-size: 13px; line-height: 1.45; }
        .city { text-align: right; margin: 0 0 28px; }
        h1 { font-size: 16px; line-height: 1.4; text-align: justify; margin: 0 0 28px; }
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
    $value = fn ($value, string $label) => filled($value) ? $value : "!!! ΛΕΙΠΕΙ: {$label} !!!";
    $period = $value($student->startDate?->format('Y-m-d'), 'ΗΜΕΡΟΜΗΝΙΑ ΕΝΑΡΞΗΣ').' έως '
        .$value($student->endDate?->format('Y-m-d'), 'ΗΜΕΡΟΜΗΝΙΑ ΛΗΞΗΣ');
    if ($student->diakoptomeno) {
        $period .= ' (Υλοποίηση Πρακτικής Άσκησης: από ['
            .$value($student->startDate?->format('Y-m-d'), 'ΗΜΕΡΟΜΗΝΙΑ ΕΝΑΡΞΗΣ').' - '
            .$value($student->endDate_DKPT_A?->format('Y-m-d'), 'ΛΗΞΗ Α΄ ΠΕΡΙΟΔΟΥ').'] και ['
            .$value($student->startDate_DKPT_B?->format('Y-m-d'), 'ΕΝΑΡΞΗ Β΄ ΠΕΡΙΟΔΟΥ').' - '
            .$value($student->endDate?->format('Y-m-d'), 'ΗΜΕΡΟΜΗΝΙΑ ΛΗΞΗΣ').'])';
    }
@endphp

<p class="city">Αθήνα, {{ now()->format('d/m/Y') }}</p>
<h1>ΒΕΒΑΙΩΣΗ ΕΚΤΕΛΕΣΗΣ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ ΦΟΙΤΗΤΩΝ/ΤΡΙΩΝ<br>
ΑΠΟ ΤΟΝ/ΤΗΝ ΝΟΜΙΜΟ/Η ΕΚΠΡΟΣΩΠΟ ΤΟΥ ΦΟΡΕΑ ΑΠΑΣΧΟΛΗΣΗΣ</h1>

<p>
    Βεβαιώνουμε ότι ο/η <strong>{{ mb_strtoupper($value($student->full_name, 'ΟΝΟΜΑ ΦΟΙΤΗΤΗ')) }}</strong>
    φοιτητής/τρια του Τμήματος <strong>{{ $value($department?->tmhma, 'ΤΜΗΜΑ') }}</strong> του Εθνικού και
    Καποδιστριακού Πανεπιστημίου Αθηνών, με Αριθμό Μητρώου Σχολής
    <strong>{{ $value($student->AMF, 'ΑΜ') }}</strong>, πραγματοποίησε Πρακτική Άσκηση στον φορέα απασχόλησης:
    <strong>{{ mb_strtoupper($value($provider?->f_epwnymia, 'ΦΟΡΕΑΣ ΑΠΑΣΧΟΛΗΣΗΣ')) }}</strong>
    κατά το χρονικό διάστημα από {{ $period }}.
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
    <p><strong>(Όνομα-Επώνυμο, υπογραφή και σφραγίδα)</strong></p>
</div>
</body>
</html>
