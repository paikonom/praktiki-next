@extends('layouts.portal', ['portalRole' => $portalRole, 'activePortalPage' => $activePortalPage])

@section('title', $pageTitle)

@section('portal-content')
    <div class="alert alert-info">Οι ενότητες είναι κλειστές αρχικά. Επιλέξτε μία για να δείτε τα αντίστοιχα στοιχεία.</div>

    @include('shared.internship-details', [
        'internshipDetailGroups' => $internshipDetailGroups,
        'accordionId' => $portalRole.'-'.$activePortalPage,
        'flat' => $flat ?? false,
    ])
@endsection
