
@php
    $department = $department ?? $student->department;
    $phase = $phase ?? $student->phase;
    $funding = $funding ?? $phase?->praktiki?->funding;
    $currentState = $currentState ?? \App\Support\StudentWorkflow::currentState($student, null);

    $summaryValue = fn ($item) => filled($item) ? $item : '—';
    $summaryName = trim($student->lName.' '.$student->fName);

    $summaryStateLabels = [
        'legacy' => 'Legacy',
        'before' => 'Πριν την έναρξη',
        'during' => 'Κατά τη διάρκεια',
        'after' => 'Μετά τη λήξη',
        'completed' => 'Ολοκληρωμένη',
        'canceled' => 'Ακυρωμένη',
    ];

    $summaryStateClasses = [
        'legacy' => 'text-bg-dark',
        'before' => 'text-bg-secondary',
        'during' => 'text-bg-primary',
        'after' => 'text-bg-warning',
        'completed' => 'text-bg-success',
        'canceled' => 'text-bg-danger',
    ];
@endphp

<div class="card shadow-sm mb-4 {{ $student->isCancelled() ? 'border-danger bg-danger-subtle' : ($student->isCompleted() ? 'border-success bg-success-subtle' : 'border-0') }}">
    <div class="card-body p-3">

        {{-- Βασικά στοιχεία φοιτητή --}}
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">

            <div class="flex-grow-1" style="min-width: 0;">

                <h1 class="h4 mb-2">
                    {{ $summaryName ?: 'Φοιτητής '.$student->id }}
                </h1>

                {{-- ΑΜ, UUID, ΑΔΤ --}}
                <div class="small text-body-secondary d-flex flex-wrap gap-2 mb-1">

                    <span>
                        ΑΜ:
                        <strong class="text-body">
                            {{ $summaryValue($student?->AMF) }}
                        </strong>
                    </span>

                    @if ($showPrivateCodes ?? false)
                        <span class="text-break">
                            UUID:
                            <strong class="text-body">
                                {{ $summaryValue($student?->uuid) }}
                            </strong>
                        </span>
                    @endif

                    <span>
                        ΑΔΤ:
                        <strong class="text-body">
                            {{ $summaryValue($student?->adt) }}
                        </strong>
                    </span>

                </div>

                {{-- Τμήμα και Υπεύθυνος Πρακτικής Τμήματος --}}
                <div class="small text-body-secondary d-flex flex-wrap column-gap-3 row-gap-1 mb-1">
                    <span>
                        Τμήμα:
                        <strong class="text-body">
                            {{ $summaryValue($department?->tmhma) }}
                        </strong>
                    </span>

                    <span>
                        Υπεύθυνος/η Π.Τ.:
                        <strong class="text-body">
                            {{ $summaryValue($student->supervisor?->full_name) }}
                        </strong>
                    </span>
                </div>

                {{-- Φορέας --}}
                <div class="small text-body-secondary d-flex flex-wrap gap-2">

                    <span>
                        Φορέας:
                        <strong class="text-body">
                            {{ $summaryValue($student->provider?->f_epwnymia) }}
                        </strong>
                    </span>

                    @if ($showPrivateCodes ?? false)
                        <span class="text-break">
                            Κωδικός:
                            <strong class="text-body">
                                {{ $summaryValue($student->kwdForea) }}
                            </strong>
                        </span>
                    @endif

                    <span>
                        Id Θέσης:
                        <strong class="text-body">
                            {{ $summaryValue($student->id_thesh) }}
                        </strong>
                    </span>

                </div>

            </div>

            {{-- Επιστροφή --}}
            @if ($backUrl)
                <a href="{{ $backUrl }}"
                   class="btn btn-sm btn-outline-secondary flex-shrink-0">
                    {{ $backLabel ?? 'Επιστροφή στους φοιτητές' }}
                </a>
            @endif

        </div>

        {{-- Προειδοποίηση ακυρωμένης πρακτικής --}}
        @if ($student->isCancelled())
            <div class="alert alert-danger fw-semibold py-2 mb-3">
                Η πρακτική έχει ακυρωθεί. Δεν επιτρέπονται αλλαγές
                στα στοιχεία φοιτητή ή φορέα.
            </div>
        @endif

        {{-- Συνοπτικά στοιχεία πρακτικής --}}
        <div class="row g-3 small">

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="text-body-secondary mb-1">
                    Χρηματοδότηση
                </div>
                <span class="badge text-bg-primary">
                    {{ $funding?->name ?? '—' }}
                </span>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="text-body-secondary mb-1">
                    Έτος / Φάση
                </div>
                <div class="fw-semibold">
                    {{ $summaryValue($student->praxh_year) }}
                    /
                    {{ $summaryValue($phase?->name) }}
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="text-body-secondary mb-1">
                    Έναρξη – Λήξη
                </div>
                <div class="fw-semibold">
                    {{ $student->startDate?->format('d/m/Y') ?? '—' }}
                    –
                    {{ $student->endDate?->format('d/m/Y') ?? '—' }}
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="text-body-secondary mb-1">
                    Κατάσταση
                </div>
                <span class="badge {{ $summaryStateClasses[$currentState] ?? 'text-bg-secondary' }}">
                    {{ $summaryStateLabels[$currentState] ?? $currentState }}
                </span>
            </div>

        </div>

        {{-- Σημειώσεις --}}
        @if ($showNotes ?? true)
            <div class="border-top mt-3 pt-3 small">
                <span class="text-body-secondary">
                    Σημειώσεις:
                </span>
                <span>
                    {{ $summaryValue($student->notes) }}
                </span>
            </div>
        @endif

    </div>
</div>
