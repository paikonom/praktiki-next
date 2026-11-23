@extends('layouts.app')

@section('title', 'Προεπισκόπηση RESCOM')

@section('content')
    <h1 class="h4">Προεπισκόπηση αποστολής στο RESCOM</h1>
    <dl class="row">
        <dt class="col-sm-3">Φοιτητής</dt><dd class="col-sm-9">{{ $student->full_name }}</dd>
        <dt class="col-sm-3">Φορέας</dt><dd class="col-sm-9">{{ data_get($data, 'Φορέας_Πρακτικής_Άσκησης.Επωνυμία') }}</dd>
        <dt class="col-sm-3">Αριθμός έργου</dt><dd class="col-sm-9">{{ $data['Έργου_Αριθμός'] }}</dd>
        <dt class="col-sm-3">Διάρκεια</dt><dd class="col-sm-9">{{ $student->startDate->format('d/m/Y') }} – {{ $student->endDate->format('d/m/Y') }}</dd>
        <dt class="col-sm-3">Συνολικό ποσό</dt><dd class="col-sm-9">{{ $data['Συνολικό_Ποσό'] }} €</dd>
    </dl>
    @if ($student->rescom_resp !== null && data_get($student->rescom_resp, '_submission.status') !== 'rejected')
        <p class="alert alert-warning">Υπάρχει ήδη απάντηση ή εκκρεμής αποστολή για αυτή την πρακτική.</p>
    @else
        <form method="POST" action="{{ route('students.rescom.store', $student) }}" class="d-inline">
            @csrf
            <input type="hidden" name="signature" value="{{ $signature }}">
            <button type="submit" class="btn btn-primary">Επιβεβαίωση αποστολής</button>
        </form>
    @endif
    <a href="{{ route('students.show', $student) }}" class="btn btn-outline-secondary">Επιστροφή στην καρτέλα</a>
@endsection
