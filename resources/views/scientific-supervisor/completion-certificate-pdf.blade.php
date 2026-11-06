<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>Βεβαίωση ολοκλήρωσης πρακτικής άσκησης</title>
    <style>
        @page { margin: 45px 50px 105px; }
        body { font-family: DejaVu Serif, serif; color: #000; font-size: 13px; line-height: 1.42; }
        .document-code, .city { text-align: right; margin: 0 0 10px; }
        h1 { font-size: 15px; line-height: 1.35; text-align: center; margin: 28px 0 22px; }
        p { text-align: justify; margin: 0 0 5px; }
        .signature { border-top: 1px solid #777; margin-top: 24px; padding-top: 24px; text-align: center; page-break-inside: avoid; }
        .signature p { text-align: center; margin-bottom: 12px; }
        .missing { color: #c00; font-weight: bold; }
    </style>
</head>
<body>
@include('pdf.partials.espa-footer')
@php
    $value = fn ($value, string $label) => filled($value) ? $value : "!!! ΛΕΙΠΕΙ: {$label} !!!";
    $studentName = mb_strtoupper($value($student->full_name, 'ΟΝΟΜΑ ΦΟΙΤΗΤΗ'));
    $providerName = mb_strtoupper($value($provider?->f_epwnymia, 'ΦΟΡΕΑΣ ΑΠΑΣΧΟΛΗΣΗΣ'));
    $supervisorName = mb_strtoupper($value($supervisor?->full_name, 'ΕΠ/ΥΠ'));
    $departmentName = mb_strtoupper($value($department?->tmhma, 'ΤΜΗΜΑ'));
    $city = $value($department?->polh, 'ΠΟΛΗ');
    $amount = filled($student->payTotal)
        ? number_format((float) $student->payTotal, fmod((float) $student->payTotal, 1.0) === 0.0 ? 0 : 2, ',', '.')
        : '!!! ΛΕΙΠΕΙ: ΣΥΝΟΛΙΚΗ ΑΜΟΙΒΗ !!!';
@endphp

<p class="document-code">Ε-ΔΠ-07-Ε22.V2.</p>
<p class="city">{{ $city }},</p>

<h1>ΒΕΒΑΙΩΣΗ ΟΛΟΚΛΗΡΩΣΗΣ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ ΦΟΙΤΗΤΗ/ΤΡΙΑΣ ΑΠΟ<br>
ΤΟΝ/ΤΗΝ ΕΠΙΣΤΗΜΟΝΙΚΟ/Η ΥΠΕΥΘΥΝΟ/Η ΤΟΥ ΤΜΗΜΑΤΟΣ {{ $departmentName }} ΤΟΥ<br>
ΕΘΝΙΚΟΥ &amp; ΚΑΠΟΔΙΣΤΡΙΑΚΟΥ ΠΑΝΕΠΙΣΤΗΜΙΟΥ ΑΘΗΝΩΝ</h1>

<p>
    Βεβαιώνεται ότι ο/η <strong>{{ $studentName }}</strong> φοιτητής/τρια του Εθνικού και Καποδιστριακού
    Πανεπιστημίου Αθηνών, με ΑΜ <strong>{{ $value($student->AMF, 'ΑΜ') }}</strong> υλοποίησε την Πρακτική του Άσκηση στο πλαίσιο
    της Πράξης «{{ $value($details['titlos_praxhs'] ?? null, 'ΤΙΤΛΟΣ ΠΡΑΞΗΣ') }}» με Κωδικό
    ΟΠΣ <strong>{{ $value($details['kwdikos_OPS'] ?? null, 'ΚΩΔΙΚΟΣ ΟΠΣ') }}</strong> του Προγράμματος
    «{{ $value($details['titlos_programmatos'] ?? null, 'ΤΙΤΛΟΣ ΠΡΟΓΡΑΜΜΑΤΟΣ') }}» η οποία
    συγχρηματοδοτείται από την Ευρωπαϊκή Ένωση (ΕΚΤ+) και από εθνικούς πόρους κατά το
    χρονικό διάστημα από <strong>{{ $value($student->startDate?->format('Y-m-d'), 'ΗΜΕΡΟΜΗΝΙΑ ΕΝΑΡΞΗΣ') }}</strong>
    έως <strong>{{ $value($student->endDate?->format('Y-m-d'), 'ΗΜΕΡΟΜΗΝΙΑ ΛΗΞΗΣ') }}</strong>, στον φορέα απασχόλησης
    <strong>{{ $providerName }}</strong> και έχει εκπληρώσει τις σχετικές του/της υποχρεώσεις.
</p>
<p>
    Η συνολική αμοιβή του/της ασκούμενου/νης για το διάστημα της πρακτικής άσκησης ανέρχεται στο
    ποσό των <strong>{{ $amount }} ευρώ</strong>.
</p>

<div class="signature">
    <p><strong>Ο/Η Επιστημονικά Υπεύθυνος/η Τμήματος</strong></p>
    <p><strong>{{ $supervisorName }}</strong></p>
    <!-- <p>{{ $supervisorName }}</p> -->
    <!-- <p>{{ now()->format('d.m.Y H:i') }}</p> -->
</div>
</body>
</html>
