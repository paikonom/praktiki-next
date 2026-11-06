<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h5 fw-bold mb-1">Αρχικό συμφωνητικό</h2>
            <div class="text-body-secondary">{{ $funding?->name ?? '—' }} · χωρίς υπογραφές</div>
        </div>
    </div>
    <div class="card-body">
        @if ($agreementMissing->isNotEmpty())
            <div class="alert alert-warning">
                <div class="fw-semibold">Λείπουν {{ $agreementMissing->count() }} στοιχεία.</div>
                <div class="small mb-2">Η προεπισκόπηση παραμένει διαθέσιμη και τα κενά θα εμφανιστούν κόκκινα ως !!!IS EMPTY!!!.</div>
                <ul class="mb-0 small">
                    @foreach ($agreementMissing as $missingField)
                        <li>{{ $missingField }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="alert alert-success">Όλα τα βασικά στοιχεία του συμφωνητικού είναι συμπληρωμένα.</div>
        @endif

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('students.agreement.preview', $student) }}" target="_blank" rel="noopener">
                Προεπισκόπηση PDF
            </a>
            <a class="btn btn-outline-secondary" href="{{ route('students.show', ['student' => $student, 'panel' => 'documents']) }}">
                Μετάβαση στα Έντυπα και ιστορικό
            </a>
        </div>
    </div>
</div>
