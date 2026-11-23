Η Πρακτική Άσκηση αρχίζει την
<x-pdf.value :value="$student->startDate?->format('Y-m-d')" />
και λήγει την
<x-pdf.value :value="$student->endDate?->format('Y-m-d')" />.
@if ($student->diakoptomeno)
    Η υλοποίηση πραγματοποιείται στα διαστήματα
    [<x-pdf.value :value="$student->startDate?->format('Y-m-d')" /> –
    <x-pdf.value :value="$student->endDate_DKPT_A?->format('Y-m-d')" />]
    και
    [<x-pdf.value :value="$student->startDate_DKPT_B?->format('Y-m-d')" /> –
    <x-pdf.value :value="$student->endDate?->format('Y-m-d')" />].
@endif
