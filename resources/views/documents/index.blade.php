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

@if ($role === 'member')
    <div class="d-flex justify-content-end mb-3">
        <a class="btn btn-outline-primary" href="{{ route('students.documents.merge', $student) }}">
            <i class="bi bi-files me-1"></i>Ενιαίο PDF εντύπων
        </a>
    </div>
@endif

@php
    $showsUploadInstructions = $types->contains(fn ($type) => $type->roles->contains(
        fn ($typeRole) => $typeRole->role === $role && $typeRole->can_upload
    ));
@endphp

@if ($showsUploadInstructions)
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
@endif

@php
    $lastPeriod = null;
    $fundingCode = $student->fundingCode();
    $allowMemberActions ??= true;
@endphp
@forelse ($types as $type)
    @if ($lastPeriod !== $type->period($fundingCode))
        <h2 class="h5 mt-4 mb-3">{{ $type->periodLabel($fundingCode) }}</h2>
        @php
            $lastPeriod = $type->period($fundingCode);
        @endphp
    @endif
    @include('documents.partials.document-card')
@empty
    <div class="alert alert-info">Δεν υπάρχουν διαθέσιμοι τύποι εγγράφων.</div>
@endforelse
