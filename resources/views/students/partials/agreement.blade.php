<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="h5 fw-bold mb-1">Αρχικό συμφωνητικό</h2>
            <div class="text-body-secondary">{{ $funding?->name ?? '—' }} · χωρίς υπογραφές</div>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('students.agreement.preview', $student) }}" target="_blank" rel="noopener">
                Προεπισκόπηση PDF
            </a>
            <form method="POST" action="{{ route('students.agreement.store', $student) }}">
                @csrf
                <button class="btn btn-success" type="submit" @disabled($student->hasApprovedDocument('symf_initial'))>
                    {{ $student->hasApprovedDocument('symf_initial') ? 'Εστάλει στον φοιτητή' : 'Στείλτο στα έντυπα του Φοιτητή' }}
                </button>
            </form>
            @if ($agreementEmail)
                <x-email-send-button :student="$student" :email="$agreementEmail" />
            @endif
            <a class="btn btn-outline-secondary" href="{{ route('students.show', ['student' => $student, 'panel' => 'documents']) }}">
                Μετάβαση στα Έντυπα και ιστορικό
            </a>
        </div>
    </div>
</div>
