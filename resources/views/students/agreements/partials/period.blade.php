Η Πρακτική Άσκηση αρχίζει την
<x-agreement.value :value="$student->startDate?->format('d/m/Y')" />
και λήγει την
<x-agreement.value :value="$student->endDate?->format('d/m/Y')" />.
@if ($student->diakoptomeno)
    Η υλοποίηση πραγματοποιείται στα διαστήματα
    [<x-agreement.value :value="$student->startDate?->format('d/m/Y')" /> –
    <x-agreement.value :value="$student->endDate_DKPT_A?->format('d/m/Y')" />]
    και
    [<x-agreement.value :value="$student->startDate_DKPT_B?->format('d/m/Y')" /> –
    <x-agreement.value :value="$student->endDate?->format('d/m/Y')" />].
@endif
