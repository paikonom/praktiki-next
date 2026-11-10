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
    $studentName = filled($student->full_name) ? mb_strtoupper($student->full_name) : null;
    $providerName = filled($provider?->f_epwnymia) ? mb_strtoupper($provider->f_epwnymia) : null;
    $supervisorName = filled($supervisor?->full_name) ? mb_strtoupper($supervisor->full_name) : null;
    $departmentName = filled($department?->tmhma) ? mb_strtoupper($department->tmhma) : null;
    $amount = filled($student->payTotal)
        ? number_format((float) $student->payTotal, fmod((float) $student->payTotal, 1.0) === 0.0 ? 0 : 2, ',', '.')
        : null;
@endphp

<p class="document-code">Ε-ΔΠ-07-Ε22.V2.</p>
<p class="city"><x-pdf.value :value="$department?->polh" />,</p>

<h1>Βεβαίωση Ολοκλήρωσης Πρακτικής Άσκησης φοιτητή/τριας από τον/την Υπεύθυνο/η Πρακτικής του Τμήματος
     <x-pdf.value :value="$departmentName" /> ΤΟΥ<br>
Εθνικού &amp; Καποδιστριακού Πανεπιστημίου Αθηνών</h1>

<p>
    Βεβαιώνεται ότι ο/η <x-pdf.value :value="$studentName" /> φοιτητής/τρια του Εθνικού και Καποδιστριακού
    Πανεπιστημίου Αθηνών, με ΑΜ <x-pdf.value :value="$student->AMF" /> υλοποίησε την Πρακτική του Άσκηση στο πλαίσιο
    της Πράξης «<x-pdf.value :value="$details['titlos_praxhs'] ?? null" />» με Κωδικό
    ΟΠΣ <x-pdf.value :value="$details['kwdikos_OPS'] ?? null" /> του Προγράμματος
    «<x-pdf.value :value="$details['titlos_programmatos'] ?? null" />» η οποία
    συγχρηματοδοτείται από την Ευρωπαϊκή Ένωση (ΕΚΤ+) και από εθνικούς πόρους κατά το
    χρονικό διάστημα από <x-pdf.value :value="$student->startDate?->format('Y-m-d')" />
    έως <x-pdf.value :value="$student->endDate?->format('Y-m-d')" />, στον φορέα απασχόλησης
    <x-pdf.value :value="$providerName" /> και έχει εκπληρώσει τις σχετικές του/της υποχρεώσεις.
</p>
<p>
    Η συνολική αμοιβή του/της ασκούμενου/νης για το διάστημα της πρακτικής άσκησης ανέρχεται στο
    ποσό των <x-pdf.value :value="$amount" /> ευρώ.
</p>

<div class="signature">
    <p><strong>Υπεύθυνο/η Πρακτικής του Τμήματος</strong></p>
    <p></p>
    <p></p>
    <p></p>
    <p><x-pdf.value :value="$supervisorName" /></p>
    <p>(Υπογραφή)</p>
</div>
</body>
</html>
