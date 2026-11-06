@extends('layouts.app')

@section('content')
    @php
        $portalNavigation = $portalRole === 'student'
            ? [
                'actions' => ['label' => 'Ενέργειες', 'route' => 'student.dashboard'],
                'details' => ['label' => 'Στοιχεία πρακτικής', 'route' => 'student.details'],
                'student-details' => ['label' => 'Στοιχεία φοιτητή', 'route' => 'student.details.student.edit'],
                'provider-details' => ['label' => 'Στοιχεία φορέα', 'route' => 'student.details.provider.edit'],
                'records' => ['label' => 'Δελτία και ερωτηματολόγια', 'route' => 'student.records'],
                'documents' => ['label' => 'Έντυπα και ιστορικό', 'route' => 'student.documents'],
                'contact' => ['label' => 'Επικοινωνία', 'route' => 'student.contact'],
            ]
            : [
                'actions' => ['label' => 'Ενέργειες', 'route' => 'provider.dashboard'],
                'details' => ['label' => 'Στοιχεία πρακτικής', 'route' => 'provider.details'],
                'student-details' => ['label' => 'Στοιχεία φοιτητή', 'route' => 'provider.details.section', 'parameters' => ['section' => 'student']],
                'provider-details' => ['label' => 'Στοιχεία φορέα', 'route' => 'provider.details.section', 'parameters' => ['section' => 'provider']],
                'questionnaire' => ['label' => 'Ερωτηματολόγιο', 'route' => 'provider.questionnaire'],
                'documents' => ['label' => 'Έντυπα και ιστορικό', 'route' => 'provider.documents'],
            ];
    @endphp

    @include('students.partials.summary', [
        'backUrl' => null,
        'showNotes' => false,
    ])

    <div class="row g-4 align-items-start">
        <aside class="col-lg-3 col-xl-2">
            <div class="list-group shadow-sm sticky-lg-top">
                @foreach ($portalNavigation as $key => $item)
                    <a class="list-group-item list-group-item-action {{ $activePortalPage === $key ? 'active' : '' }}"
                       href="{{ route($item['route'], $item['parameters'] ?? []) }}">{{ $item['label'] }}</a>
                @endforeach
            </div>
        </aside>

        <main class="col-lg-9 col-xl-10">
            @yield('portal-content')
        </main>
    </div>
@endsection
