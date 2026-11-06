@extends('layouts.app')

@section('title', 'Καρτέλα φοιτητή')

@section('content')
    @php
        $value = fn ($item) => filled($item) ? $item : '—';
        $navigation = [
            'office' => 'Στοιχεία γραφείου',
            'agreement' => 'Αρχικό συμφωνητικό',
            'student' => 'Στοιχεία φοιτητή',
            'provider' => 'Στοιχεία φορέα',
            'questionnaires' => 'Ερωτηματολόγια',
            'documents' => 'Έντυπα και ιστορικό',
            'emails' => 'Emails / Log',
        ];
    @endphp

    @include('students.partials.summary', [
        'backUrl' => session('students.index_url', route('students.index')),
        'showUuid' => true,
        ])

    @if (session('status'))
        <div class="alert alert-{{ session('status_type', 'success') }} alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Κλείσιμο"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold">Η αποθήκευση δεν έγινε. Διόρθωσε τα παρακάτω:</div>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4 align-items-start">
        <aside class="col-lg-3 col-xl-2">
            <div class="list-group shadow-sm sticky-lg-top">
                @foreach ($navigation as $key => $label)
                    <a class="list-group-item list-group-item-action @if ($panel === $key) active @endif"
                       href="{{ route('students.show', ['student' => $student->id, 'panel' => $key]) }}">{{ $label }}</a>
                @endforeach
                @foreach (['ResCom', 'Έλεγχοι / Προειδοποιήσεις'] as $label)
                    <button class="list-group-item list-group-item-action" type="button" disabled>{{ $label }}</button>
                @endforeach
            </div>
        </aside>

        <div class="col-lg-9 col-xl-10">
            @if ($panel === 'office')
                <div class="mb-4">
                    <h2 class="h4 mb-1">Ενέργειες χειριστή</h2>
                    <p class="small text-body-secondary mb-3">Όλες οι ενέργειες του χειριστή βρίσκονται αποκλειστικά εδώ.</p>

                    <h3 class="h5 mt-4 mb-3">Καταστάσεις ροής</h3>
                    @include('shared.workflow', ['sections' => $officeWorkflowSections, 'currentRole' => 'member', 'accordionId' => 'office-workflow'])

                </div>
                @include('students.partials.fields', ['title' => 'Στοιχεία γραφείου', 'section' => 'office', 'fields' => $officeFields, 'groups' => $fieldGroups['office'], 'fieldHelp' => $fieldHelp, 'record' => $student])
            @elseif ($panel === 'agreement')
                @include('students.partials.agreement')
            @elseif ($panel === 'student')
                @include('students.partials.fields', [
                    'title' => 'Στοιχεία φοιτητή',
                    'subtitle' => 'Τα πεδία είναι ομαδοποιημένα ώστε να ελέγχονται ευκολότερα.',
                    'section' => 'student',
                    'fields' => $studentFields,
                    'groups' => $fieldGroups['student'],
                    'fieldHelp' => $fieldHelp,
                    'record' => $student,
                ])
            @elseif ($panel === 'provider')
                @include('students.partials.fields', [
                    'title' => 'Στοιχεία Φορέα Υποδοχής (Φ.Υ.)',
                    'subtitle' => 'id: '.($provider?->id ?? '—').' · Κωδικός φορέα: '.$value($student->kwdForea),
                    'section' => 'provider',
                    'fields' => $providerFields,
                    'groups' => $fieldGroups['provider'],
                    'fieldHelp' => $fieldHelp,
                    'record' => $provider,
                ])
            @elseif ($panel === 'questionnaires')
                @php
                    $apografikoLabels = [
                        'draft' => 'Πρόχειρο',
                        'submitted' => 'Υποβλήθηκε',
                        'processing' => 'Σε επεξεργασία',
                        'completed' => 'Ολοκληρωμένο',
                        'failed' => 'Αποτυχία',
                        'not_required' => 'Δεν απαιτείται',
                        'cancelled' => 'Ακυρώθηκε στο ΟΠΣ',
                    ];
                @endphp
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h2 class="h5 mb-0">Απογραφικά δελτία ΟΠΣ</h2></div>
                    <div class="list-group list-group-flush">
                        @foreach (['input' => 'Απογραφικό δελτίο εισόδου', 'output' => 'Απογραφικό δελτίο εξόδου'] as $type => $label)
                            @php
                                $record = $student->apografika->firstWhere('type', $type);
                                $status = $record?->status;
                                $statusLabel = $apografikoLabels[$status] ?? 'Δεν υποβλήθηκε';
                                $statusActions = $handlerStatusActions['apografika'][$type] ?? [];
                            @endphp
                            <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
                                <div>
                                    <div class="fw-semibold">{{ $label }}</div>
                                    <span class="badge {{ \App\Support\StatusBadge::className($status) }}">{{ $statusLabel }}</span>
                                    @if ($record?->deltio_id)<span class="small text-muted ms-1">ID ΟΠΣ: {{ $record->deltio_id }}</span>@endif
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @if ($status === 'completed')
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('students.apografika.pdf', [$student, $type]) }}" target="_blank">PDF</a>
                                    @endif
                                    @if ($canEdit && $status === 'completed' && ($statusActions['cancelled'] ?? false))
                                        <form method="POST" action="{{ route('students.apografika.update', [$student, $type]) }}" onsubmit="return confirm('Έχει επιβεβαιωθεί η ακύρωση του δελτίου από το ΟΠΣ;')">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="cancelled">
                                            <button class="btn btn-sm btn-outline-danger">Καταχώριση ακύρωσης</button>
                                        </form>
                                    @elseif ($canEdit && $status === 'cancelled' && ($statusActions['completed'] ?? false))
                                        <form method="POST" action="{{ route('students.apografika.update', [$student, $type]) }}" onsubmit="return confirm('Να αναιρεθεί η καταχώριση ακύρωσης;')">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button class="btn btn-sm btn-outline-secondary">Αναίρεση ακύρωσης</button>
                                        </form>
                                    @elseif ($canEdit && $status === 'not_required' && ($statusActions['draft'] ?? false))
                                        <form method="POST" action="{{ route('students.apografika.update', [$student, $type]) }}" onsubmit="return confirm('Να απαιτείται ξανά το απογραφικό;')">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="draft">
                                            <button class="btn btn-sm btn-outline-secondary">Να απαιτείται ξανά</button>
                                        </form>
                                    @elseif ($canEdit && ! in_array($status, ['completed', 'cancelled', 'not_required'], true) && ($statusActions['not_required'] ?? false))
                                        <form method="POST" action="{{ route('students.apografika.update', [$student, $type]) }}" onsubmit="return confirm('Να καταχωριστεί ότι το απογραφικό δεν απαιτείται;')">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="not_required">
                                            <button class="btn btn-sm btn-outline-secondary">Δεν απαιτείται</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white"><h2 class="h5 mb-0">Ερωτηματολόγια</h2></div>
                    <div class="list-group list-group-flush">
                        @foreach ([
                            'student' => ['label' => 'Ερωτηματολόγιο φοιτητή', 'record' => $student->questionnaire],
                            'provider' => ['label' => 'Ερωτηματολόγιο φορέα', 'record' => $student->providerQuestionnaire],
                        ] as $type => $item)
                            @php
                                $questionnaireStatus = $item['record']?->status;
                                $statusActions = $handlerStatusActions['questionnaires'][$type] ?? [];
                            @endphp
                            <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
                                <div>
                                    <div class="fw-semibold">{{ $item['label'] }}</div>
                                    @if ($item['record'])
                                        <span class="badge {{ $item['record']->isSubmitted() ? 'text-bg-success' : ($item['record']->status === 'rejected' ? 'text-bg-danger' : 'text-bg-secondary') }}">
                                            {{ match ($item['record']->status) { 'submitted' => 'Υποβλήθηκε', 'rejected' => 'Απορρίφθηκε', default => 'Πρόχειρο' } }}
                                        </span>
                                        <span class="small text-muted">{{ $item['record']->submitted_at?->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="badge text-bg-secondary">Δεν υποβλήθηκε</span>
                                    @endif
                                </div>
                                <div class="d-flex gap-2">
                                    @if ($item['record'])
                                        <a class="btn btn-outline-primary" href="{{ route('students.questionnaires.show', [$student, $type]) }}">Προβολή</a>
                                        @if ($item['record']->isSubmitted())
                                            <a class="btn btn-outline-secondary" href="{{ route('students.questionnaires.pdf', [$student, $type]) }}" target="_blank">PDF</a>
                                        @endif
                                        @if ($canEdit && $questionnaireStatus === 'submitted' && ($statusActions['rejected'] ?? false))
                                            <form method="POST" action="{{ route('students.questionnaires.update', [$student, $type]) }}" onsubmit="return confirm('Να απορριφθεί το ερωτηματολόγιο ώστε να μπορεί να υποβληθεί ξανά;')">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <input type="hidden" name="from" value="list">
                                                <button class="btn btn-outline-danger">Απόρριψη</button>
                                            </form>
                                        @elseif ($canEdit && $questionnaireStatus === 'rejected' && ($statusActions['submitted'] ?? false))
                                            <form method="POST" action="{{ route('students.questionnaires.update', [$student, $type]) }}" onsubmit="return confirm('Να αναιρεθεί η απόρριψη;')">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="submitted">
                                                <input type="hidden" name="from" value="list">
                                                <button class="btn btn-outline-secondary">Αναίρεση απόρριψης</button>
                                            </form>
                                        @endif
                                    @else
                                        <button class="btn btn-outline-primary" type="button" disabled>Προβολή</button>
                                        <button class="btn btn-outline-secondary" type="button" disabled>PDF</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif ($panel === 'documents')
                @include('documents.index', [
                    'types' => $documentTypes,
                    'role' => 'member',
                    'embedded' => true,
                    'allowMemberActions' => true,
                ])
            @else
                @include('students.partials.emails')
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/rescom-city-search.js') }}" defer></script>
    <script src="{{ asset('js/bootstrap-tooltips.js') }}" defer></script>
@endpush
