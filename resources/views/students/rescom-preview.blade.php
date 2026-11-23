@extends('layouts.app')

@section('title', 'Προεπισκόπηση RESCOM')

@section('content')
    @if ($student->rescom_resp !== null)
        <h1 class="h4">Αποθηκευμένη απάντηση RESCOM</h1>
        <p>{{ $student->full_name }}</p>
        <pre class="border rounded bg-body p-3 overflow-auto">{{ json_encode($student->rescom_resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    @else
    <h1 class="h4">Προεπισκόπηση αποστολής στο RESCOM</h1>
    <dl class="row">
        <dt class="col-sm-3">Φοιτητής</dt><dd class="col-sm-9">{{ $student->full_name }}</dd>
        <dt class="col-sm-3">Φορέας</dt><dd class="col-sm-9">{{ data_get($data, 'Φορέας_Πρακτικής_Άσκησης.Επωνυμία') }}</dd>
        <dt class="col-sm-3">Αριθμός έργου</dt><dd class="col-sm-9">{{ $data['Έργου_Αριθμός'] }}</dd>
        <dt class="col-sm-3">Διάρκεια</dt><dd class="col-sm-9">{{ $student->startDate->format('Y-m-d') }} – {{ $student->endDate->format('Y-m-d') }}</dd>
        <dt class="col-sm-3">Συνολικό ποσό</dt><dd class="col-sm-9">{{ $data['Συνολικό_Ποσό'] }} €</dd>
    </dl>
        <form method="POST" action="{{ route('students.rescom.store', $student) }}" class="d-inline">
            @csrf
            <input type="hidden" name="signature" value="{{ $signature }}">
            <button type="submit" class="btn btn-primary">Επιβεβαίωση αποστολής</button>
        </form>
    @endif
    <a href="{{ route('students.show', $student) }}" class="btn btn-outline-secondary">Επιστροφή στην καρτέλα</a>
@endsection
