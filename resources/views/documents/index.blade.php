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

<div class="card shadow-sm mb-4">
    <div class="card-body">

        <div class="alert alert-danger border-0 rounded-2 py-3 px-3 mb-3" role="alert">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <strong>Προσοχή! Σημαντικές οδηγίες πριν από την υποβολή εντύπων</strong>
            </div>

            <div class="small">
                <p class="mb-2">
                    Τα περισσότερα έντυπα συνοδεύονται από συγκεκριμένες οδηγίες συμπλήρωσης.
                    <strong>Πριν συμπληρώσετε, υπογράψετε ή ανεβάσετε οποιοδήποτε έντυπο,
                    είναι απαραίτητο να διαβάσετε προσεκτικά τις αντίστοιχες οδηγίες.</strong>
                </p>

                <p class="mb-0">
                    <i class="bi bi-pen-fill me-1"></i>
                    <strong>Ιδιαίτερη προσοχή απαιτείται στον τρόπο υπογραφής των εντύπων.</strong>
                    Παρακαλούμε να διαβάσετε οπωσδήποτε την παρακάτω οδηγία,
                    ώστε να αποφύγετε λανθασμένες υποβολές και πιθανές καθυστερήσεις
                    στην έγκριση των εγγράφων σας.
                </p>
            </div>
        </div>

        <div class="accordion" id="digital-signature-help">
            <div class="accordion-item border rounded-2 overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-info-subtle"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#digital-signature-help-content"
                            aria-expanded="false"
                            aria-controls="digital-signature-help-content">
                        <i class="bi bi-pen-fill me-2" aria-hidden="true"></i>
                        Οδηγίες για την ψηφιακή υπογραφή εγγράφων
                    </button>
                </h2>

                <div id="digital-signature-help-content"
                     class="accordion-collapse collapse"
                     data-bs-parent="#digital-signature-help">
                    <div class="accordion-body">
                        <p>
                            Ψηφιακή υπογραφή σημαίνει ότι το έγγραφο υπογράφεται ηλεκτρονικά μέσω υπολογιστή ή κινητού,
                            χωρίς να χρειάζεται να το εκτυπώσετε, να το υπογράψετε με στυλό και να το σκανάρετε.
                            Μπορείτε να το κάνετε είτε μέσω του gov.gr, επιλέγοντας «Ψηφιακή Βεβαίωση Εγγράφου»
                            με τους κωδικούς Taxisnet, είτε με κάποιο εγκεκριμένο ψηφιακό πιστοποιητικό
                            (π.χ. μέσω Adobe Reader).
                            Μια απλή εικόνα υπογραφής επικολλημένη στο PDF δεν θεωρείται έγκυρη ψηφιακή υπογραφή.
                        </p>

                        <p class="fw-semibold">
                            Προσοχή: το αρχείο δεν πρέπει να τύχει οποιασδήποτε παρέμβασης μετά τις υπογραφές,
                            γιατί ενδέχεται να θεωρηθεί άκυρο.
                        </p>

                        <p class="mb-0">
                            Αν δεν υπάρχει δυνατότητα ψηφιακής υπογραφής, παρακαλούμε επικοινωνήστε
                            με τον χειριστή του τμήματός σας στο Γραφείο Πρακτικής Άσκησης του ΕΚΠΑ
                            για καθοδήγηση.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

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
        $documents = $type->documents;
        $latestDocument = $documents->first();
        $status = $latestDocument?->status;

        $canUpload = $available
            && $acceptsNewVersion
            && $type->roleCan($role, 'can_upload')
            && ($role !== 'member' || $allowMemberActions);

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
            && $acceptsNewVersion;
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
                @php
                    $uploadRoles = $type->roles->where('can_upload', true);

                    if ($uploadRoles->contains('role', 'member') && $uploadRoles->count() > 1) {
                        $uploadRoles = $uploadRoles->where('role', '!=', 'member');
                    }

                    $roleLabels = config('praktiki_workflow.role_labels');
                    $roleIcons = config('praktiki_workflow.role_icons');
                @endphp
<span class="fw-semibold">{{ $type->title }}</span>
                @foreach ($uploadRoles as $uploadRole)
                    @php
                        $roleLabel = $roleLabels[$uploadRole->role] ?? $uploadRole->role;
                        $roleIcon = $roleIcons[$uploadRole->role] ?? 'bi-person';
                    @endphp

                    <span class="badge rounded-pill bg-white text-dark border border-secondary-subtle fw-normal">                        <i class="bi {{ $roleIcon }} me-1"></i>
                        {{ $roleLabel }}
                    </span>
                @endforeach

            </span>
            <span class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <span class="badge border-0 {{ $statusClass }}">{{ $statusLabel }}</span>
                @if ($documents->isNotEmpty())
                    <span class="badge border-0 text-bg-secondary">
                        {{ $documents->count() }}
                        @if ($type->code === 'extra_docs')
                            {{ $documents->count() === 1 ? 'αρχείο' : 'αρχεία' }}
                        @else
                            {{ $documents->count() === 1 ? 'έκδοση' : 'εκδόσεις' }}
                        @endif
                    </span>
                @endif
                <span aria-hidden="true">⌄</span>
            </span>
        </button>
        <div id="document-type-{{ $type->id }}" class="collapse">
        <div class="card-body">
            @if ($status === 'not_required')
                <div class="alert alert-info mb-0">
                    <div class="fw-semibold">Δεν απαιτείται η υποβολή αυτού του εγγράφου.
                    <span class="mt-2"><strong>Αιτιολογία:</strong> {{ $latestDocument->review_notes ?: 'Δεν έχει καταχωριστεί αιτιολογία.' }}</span></div>

                    @if ($role === 'member' && $allowMemberActions && ! auth('web')->user()->view_only)
                        <form method="POST" action="{{ route('students.documents.require-again', [$student, $type]) }}" class="mt-3"
                              onsubmit="return confirm('Να απαιτείται ξανά αυτό το έγγραφο;')">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary">Να απαιτείται ξανά</button>
                        </form>
                    @endif
                </div>
            @else
                @if ($type->instructions)
                <div class="alert alert-danger border-0 rounded-2 py-2 px-3 mb-3" role="alert">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span><strong>Προσοχή!</strong> Πριν ξεκινήσετε, παρακαλούμε διαβάστε τις παρακάτω οδηγίες.</span>

                        <button class="btn btn-sm btn-outline-danger ms-auto"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#instructions-collapse-{{ $type->id }}"
                                aria-expanded="false"
                                aria-controls="instructions-collapse-{{ $type->id }}">
                            <i class="bi bi-chevron-down me-1"></i> Οδηγίες
                        </button>
                    </div>

                    <div class="collapse mt-2" id="instructions-collapse-{{ $type->id }}">
                        <div class="pt-2 border-top border-danger-subtle">
                            @php
                                $instructions = preg_replace_callback(
                                    '~<a\s+href=[\'\"](https://[^\'\"]+)[\'\"]\s*>(.*?)</a>~isu',
                                    fn (array $link): string => '['.strip_tags($link[2]).']('.$link[1].')',
                                    $type->instructions,
                                );
                            @endphp
                            {!! \Illuminate\Support\Str::markdown($instructions, [
                                'html_input' => 'strip',
                                'allow_unsafe_links' => false,
                            ]) !!}
                        </div>
                    </div>
                </div>
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
                    Προεπισκόπηση βεβαίωσης Υπεύθυνου/η Π.Τ.
                </a>
            @endif

            @if ($canUpload)
                <form method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <div class="input-group">
                        <span class="input-group-text">PDF έως {{ $type->max_size_mb }} MB</span>
                        <input class="form-control" type="file" name="file" accept="application/pdf" required>
                        <button class="btn btn-outline-primary">
                            {{ $type->code === 'extra_docs' ? 'Ανέβασμα αρχείου' : 'Ανέβασμα νέας έκδοσης' }}
                        </button>
                    </div>
                </form>
            @elseif (! $available)
                <div class="alert alert-warning mb-3">
                    {{ match ($availabilityIssue) {
                        'missing_end_date' => 'Δεν έχει οριστεί ημερομηνία λήξης της πρακτικής.',
                        'internship_not_started' => 'Το ανέβασμα θα είναι διαθέσιμο μετά την έναρξη της πρακτικής.',
                        'internship_active' => 'Το ανέβασμα θα είναι διαθέσιμο μετά τη λήξη της πρακτικής.',
                        'funding' => 'Το έγγραφο δεν είναι διαθέσιμο για τη χρηματοδότηση του φοιτητή.',
                        'previous_step' => 'Πρέπει πρώτα να ολοκληρωθεί το προηγούμενο βήμα της διαδικασίας. Δείτε το menu ενέργειες για περισσότερες πληροφορίες.',
                        'previous_sections' => 'Πρέπει πρώτα να ολοκληρωθούν οι ενέργειες Α και Β.',
                        default => 'Ο τύπος εγγράφου δεν είναι ενεργός.',
                    } }}
                </div>
            @endif

            @if ($canMarkNotRequired && $type->code !== 'extra_docs')
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
                                $actor = match ($role) {
                                    'member' => auth('web')->user(),
                                    'scientific_supervisor' => auth('scientific_supervisor')->user(),
                                    default => auth($role)->user(),
                                };
                                $canDelete = $actor && Gate::forUser($actor)->allows('delete', [$document, $role]);
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
                                    @if ($type->code !== 'extra_docs' && $document->path && $role === 'member' && $allowMemberActions && ! auth('web')->user()->view_only)
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
            @endif
        </div>
        </div>
    </div>
@empty
    <div class="alert alert-info">Δεν υπάρχουν διαθέσιμοι τύποι εγγράφων.</div>
@endforelse
