@php
    $department = $department ?? $student->department;
    $phase = $phase ?? $student->phase;
    $funding = $funding ?? $phase?->praktiki?->funding;
    $currentState = $currentState ?? \App\Support\StudentWorkflow::currentState($student, null);
    $summaryValue = fn ($item) => filled($item) ? $item : '—';
    $summaryName = trim($student->lName.' '.$student->fName);
    $summaryStateLabels = ['legacy' => 'Legacy', 'before' => 'Πριν την έναρξη', 'during' => 'Κατά τη διάρκεια', 'after' => 'Μετά τη λήξη', 'completed' => 'Ολοκληρωμένη', 'canceled' => 'Ακυρωμένη'];
    $summaryStateClasses = ['legacy' => 'text-bg-dark', 'before' => 'text-bg-secondary', 'during' => 'text-bg-primary', 'after' => 'text-bg-warning', 'completed' => 'text-bg-success', 'canceled' => 'text-bg-danger'];
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">{{ $summaryName ?: 'Φοιτητής '.$student->id }}</h1>
                <div class="small text-body-secondary">
                    ΑΜ: {{ $summaryValue($student->AMF) }} · sid: {{ $student->id }} · {{ $summaryValue($department?->tmhma) }}
                </div>
                <div class="small text-body-secondary">
                    Επιστημονικός Υπεύθυνος:
                    <span class="fw-semibold text-body">{{ $summaryValue($student->supervisor?->full_name) }}</span>
                </div>
            </div>
            @if ($backUrl)
                <a href="{{ $backUrl }}" class="btn btn-sm btn-outline-secondary">{{ $backLabel ?? 'Επιστροφή στους φοιτητές' }}</a>
            @endif
        </div>

        <div class="row g-3 small">
            <div class="col-6 col-md-3">
                <div class="text-body-secondary">Χρηματοδότηση</div>
                <span class="badge text-bg-primary">{{ $funding?->name ?? '—' }}</span>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-body-secondary">Έτος / Φάση</div>
                <div class="fw-semibold">{{ $summaryValue($student->praxh_year) }} / {{ $summaryValue($phase?->name) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-body-secondary">Έναρξη – Λήξη</div>
                <div class="fw-semibold text-nowrap">{{ $student->startDate?->format('d/m/Y') ?? '—' }} – {{ $student->endDate?->format('d/m/Y') ?? '—' }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-body-secondary">Κατάσταση</div>
                <span class="badge {{ $summaryStateClasses[$currentState] ?? 'text-bg-secondary' }}">{{ $summaryStateLabels[$currentState] ?? $currentState }}</span>
            </div>
        </div>

        <div class="row g-2 small border-top mt-3 pt-3">
            <div class="col-12 col-lg-6">
                <span class="text-body-secondary">Φορέας:</span>
                <span class="fw-semibold">{{ $summaryValue($student->provider?->f_epwnymia) }}</span>
            </div>
            @if ($showNotes ?? true)
                <div class="col-12 col-lg-6">
                    <span class="text-body-secondary">Σημειώσεις:</span>
                    <span>{{ $summaryValue($student->notes) }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
