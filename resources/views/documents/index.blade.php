@if (! ($embedded ?? false))
    <div class="d-flex justify-content-between align-items-start mb-4">
        <!-- <div>
            <h1 class="h3 mb-1">Έντυπα</h1>
            <p class="text-muted mb-0">{{ $student->full_name }} · {{ $student->fundingCode() }}</p>
        </div> -->
        @if ($role === 'member')
            <a class="btn btn-outline-secondary" href="{{ route('students.show', $student) }}">Επιστροφή</a>
        @endif
    </div>
@endif

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@php
    $lastPeriod = null;
    $fundingCode = $student->fundingCode();
    $allowMemberActions ??= true;
@endphp
@forelse ($types as $type)
    @php
        $availabilityIssue = $type->availabilityIssueFor($student, $role);
        $available = $availabilityIssue === null;
        $acceptsNewVersion = $type->acceptsNewVersionFor($student);
        $canUpload = $available && $acceptsNewVersion && $type->roleCan($role, 'can_upload')
            && ($role !== 'member' || $allowMemberActions);
        $documents = $type->documents;
        $latestDocument = $documents->first();
        $status = $latestDocument?->status;
        $statusLabel = \App\Support\StatusBadge::label($status);
        $statusClass = \App\Support\StatusBadge::className($status);
        $borderClass = \App\Support\StatusBadge::borderClass($status);
        $headerClass = \App\Support\StatusBadge::backgroundClass($status);
        $storeRoute = match ($role) {
            'student' => route('student.documents.store', $type),
            'provider' => route('provider.documents.store', $type),
            'scientific_supervisor' => route('scientific_supervisor.documents.store', [$student, $type]),
            default => route('students.documents.store', [$student, $type]),
        };
        $canMarkNotRequired = $role === 'member'
            && $allowMemberActions
            && ! auth('web')->user()->view_only
            && $available
            && $acceptsNewVersion
            && $status !== 'not_required';
    @endphp
    @if ($lastPeriod !== $type->period($fundingCode))
        <h2 class="h5 mt-4 mb-3">{{ $type->periodLabel($fundingCode) }}</h2>
        @php
            $lastPeriod = $type->period($fundingCode);
        @endphp
    @endif
    <div class="card shadow-sm mb-3 border-0">
        <button class="card-header border-0 d-flex justify-content-between align-items-center gap-2 text-start {{ $headerClass }}"
                type="button" data-bs-toggle="collapse" data-bs-target="#document-type-{{ $type->id }}"
                aria-expanded="false" aria-controls="document-type-{{ $type->id }}">
            <span class="d-flex align-items-center gap-2 flex-wrap">
                @foreach ($type->roles->where('can_upload', true) as $uploadRole)
                    <span class="badge rounded-pill border-0 {{ $uploadRole->role === 'student' ? 'text-bg-primary' : ($uploadRole->role === 'provider' ? 'text-bg-success' : 'text-bg-secondary') }}">
                        {{ ['student' => 'Φοιτητής', 'provider' => 'Φορέας', 'scientific_supervisor' => 'ΕΠ/ΥΠ', 'member' => 'Χειριστής'][$uploadRole->role] }}
                    </span>
                @endforeach
                <span class="fw-semibold">{{ $type->title }}</span>
            </span>
            <span class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <span class="badge border-0 {{ $statusClass }}">{{ $statusLabel }}</span>
                @if ($documents->isNotEmpty())
                    <span class="badge border-0 text-bg-secondary">{{ $documents->count() }} {{ $documents->count() === 1 ? 'έκδοση' : 'εκδόσεις' }}</span>
                @endif
                <span aria-hidden="true">⌄</span>
            </span>
        </button>
        <div id="document-type-{{ $type->id }}" class="collapse">
        <div class="card-body">
            @if ($type->instructions)
                <p class="text-muted">{{ $type->instructions }}</p>
            @endif

            @if ($role === 'scientific_supervisor' && $fundingCode === 'Espa' && $type->code === 'vevaiwsh_EY')
                <a class="btn btn-outline-primary mb-3" href="{{ route('scientific_supervisor.completion-certificate.preview', $student) }}" target="_blank" rel="noopener">
                    Προεπισκόπηση έτοιμης βεβαίωσης PDF
                </a>
            @endif

            @if ($role === 'provider' && $type->code === 'vevaiwsh_Forea')
                <a class="btn btn-outline-primary mb-3" href="{{ route('provider.completion-certificate.preview') }}" target="_blank" rel="noopener">
                    Προεπισκόπηση έτοιμης βεβαίωσης PDF
                </a>
            @endif

            @if ($role === 'member' && $type->code === 'vevaiwsh_Forea')
                <a class="btn btn-outline-primary mb-3" href="{{ route('students.completion-certificates.provider', $student) }}" target="_blank" rel="noopener">
                    Προεπισκόπηση βεβαίωσης φορέα
                </a>
            @endif

            @if ($role === 'member' && $fundingCode === 'Espa' && $type->code === 'vevaiwsh_EY')
                <a class="btn btn-outline-primary mb-3" href="{{ route('students.completion-certificates.supervisor', $student) }}" target="_blank" rel="noopener">
                    Προεπισκόπηση βεβαίωσης ΕΠ/ΥΠ
                </a>
            @endif

            @if ($canUpload)
                <form method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <div class="input-group">
                        <span class="input-group-text">PDF έως {{ $type->max_size_mb }} MB</span>
                        <input class="form-control" type="file" name="file" accept="application/pdf" required>
                        <button class="btn btn-outline-primary">Ανέβασμα νέας έκδοσης</button>
                    </div>
                </form>
            @elseif (! $available)
                <div class="alert alert-warning mb-3">
                    {{ match ($availabilityIssue) {
                        'missing_end_date' => 'Δεν έχει οριστεί ημερομηνία λήξης της πρακτικής.',
                        'internship_not_started' => 'Το ανέβασμα θα είναι διαθέσιμο μετά την έναρξη της πρακτικής.',
                        'internship_active' => 'Το ανέβασμα θα είναι διαθέσιμο μετά τη λήξη της πρακτικής.',
                        'funding' => 'Το έγγραφο δεν είναι διαθέσιμο για τη χρηματοδότηση του φοιτητή.',
                        'previous_step' => 'Πρέπει πρώτα να ολοκληρωθεί το προηγούμενο βήμα της διαδικασίας.',
                        'previous_sections' => 'Πρέπει πρώτα να ολοκληρωθούν οι ενότητες Α και Β.',
                        default => 'Ο τύπος εγγράφου δεν είναι ενεργός.',
                    } }}
                </div>
            @endif

            @if ($canMarkNotRequired)
                <form method="POST" action="{{ route('students.documents.not-required', [$student, $type]) }}" class="d-flex gap-2 mb-3">
                    @csrf
                    <input class="form-control" name="review_notes" maxlength="2000" placeholder="Αιτιολογία" required>
                    <button class="btn btn-outline-info text-nowrap">Δεν απαιτείται</button>
                </form>
            @endif

            @if ($documents->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Έκδοση</th><th>Αρχείο</th><th>Ανέβηκε</th><th>Κατάσταση</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($documents as $document)
                            @php
                                $downloadRoute = match ($role) {
                                    'student' => route('student.documents.download', $document),
                                    'provider' => route('provider.documents.download', $document),
                                    'scientific_supervisor' => route('scientific_supervisor.documents.download', $document),
                                    default => route('students.documents.download', $document),
                                };
                                $previewRoute = match ($role) {
                                    'student' => route('student.documents.preview', $document),
                                    'provider' => route('provider.documents.preview', $document),
                                    'scientific_supervisor' => route('scientific_supervisor.documents.preview', $document),
                                    default => route('students.documents.preview', $document),
                                };
                                $deleteRoute = match ($role) {
                                    'student' => route('student.documents.destroy', $document),
                                    'provider' => route('provider.documents.destroy', $document),
                                    'scientific_supervisor' => route('scientific_supervisor.documents.destroy', $document),
                                    default => route('students.documents.destroy', $document),
                                };
                                $actorId = match ($role) {
                                    'member' => auth('web')->id(),
                                    'scientific_supervisor' => auth('scientific_supervisor')->id(),
                                    default => auth($role)->id(),
                                };
                                $canDelete = ($role === 'member' && auth('web')->user()?->role === 'admin')
                                    || ($document->status === 'submitted' && $document->uploaded_by_role === $role && $document->uploaded_by_id === $actorId);
                            @endphp
                            <tr>
                                <td>v{{ $document->version }}</td>
                                <td>
                                    @if ($document->path)
                                        <a href="{{ $previewRoute }}" target="_blank" rel="noopener">{{ $document->original_name }}</a>
                                    @else
                                        <span class="text-muted">Δεν υπάρχει αρχείο</span>
                                    @endif
                                </td>
                                <td>
                                    {{ ($document->uploaded_at ?? $document->reviewed_at)?->format('d/m/Y H:i') ?? '—' }}
                                    @if ($document->uploaded_by_role)<br><small class="text-muted">{{ $document->uploaded_by_role }}</small>@endif
                                </td>
                                <td>
                                    <span class="badge border border-white {{ \App\Support\StatusBadge::className($document->status) }}">
                                        {{ [
                                            'submitted' => 'Αναμονή ελέγχου',
                                            'approved' => 'Εγκρίθηκε',
                                            'not_required' => 'Δεν απαιτείται',
                                            'rejected' => 'Απορρίφθηκε',
                                        ][$document->status] }}
                                    </span>
                                    @if ($document->review_notes)<div class="small mt-1">{{ $document->review_notes }}</div>@endif
                                </td>
                                <td class="text-end">
                                    @if ($document->path)
                                        <a class="btn btn-sm btn-outline-primary" href="{{ $downloadRoute }}">Λήψη</a>
                                    @endif
                                    @if ($document->path && $role === 'member' && $allowMemberActions && ! auth('web')->user()->view_only)
                                        <form method="POST" action="{{ route('students.documents.update', $document) }}" class="d-inline-flex gap-1">
                                            @csrf @method('PATCH')
                                            @if ($document->status !== 'approved')
                                                <button class="btn btn-sm btn-outline-success" name="status" value="approved">Έγκριση</button>
                                            @endif
                                            @if ($document->status !== 'rejected')
                                                <input class="form-control form-control-sm" name="review_notes" placeholder="Αιτιολογία απόρριψης">
                                                <button class="btn btn-sm btn-outline-danger" name="status" value="rejected">Απόρριψη</button>
                                            @endif
                                        </form>
                                    @endif
                                    @if ($canDelete && ($role !== 'member' || $allowMemberActions))
                                        <form method="POST" action="{{ $deleteRoute }}" class="d-inline" onsubmit="return confirm('Οριστική διαγραφή του αρχείου;')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Διαγραφή</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        </div>
    </div>
@empty
    <div class="alert alert-info">Δεν υπάρχουν διαθέσιμοι τύποι εγγράφων.</div>
@endforelse
