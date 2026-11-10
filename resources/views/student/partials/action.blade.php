
@php

// Αν δεν υπάρχει ακόμη status, θεωρούμε την ενέργεια εκκρεμή.
$effectiveStatus = $status ?? 'pending';
    $currentRole ??= 'student';
    $responsible ??= $currentRole;

    $isResponsible = $responsible === $currentRole;

    $responsibleLabel = config('praktiki_workflow.role_labels.'.$responsible, $responsible);
    $roleIcons = config('praktiki_workflow.role_icons');

    // Οδηγίες ανά τύπο ενέργειας
    $actionHint = match ($sourceType ?? null) {
        'document' =>
            'Επιλέξτε το μενού «Έντυπα και Ιστορικό» για να δείτε τις οδηγίες.',

        'student_questionnaire', 'provider_questionnaire', 'apografiko' =>
            'Επιλέξτε το μενού «Δελτία / Αξιολογήσεις» για την ολοκλήρωση της ενέργειας.',

        default => null,
    };

    // Τα ερωτηματολόγια ολοκληρώνονται με submitted,
    // χωρίς να απαιτείται επιπλέον έγκριση.
    $isCompletedQuestionnaire = $done
        && in_array($sourceType ?? null, [
            'student_questionnaire',
            'provider_questionnaire',
        ], true)
        && ($status ?? null) === 'submitted';

    // Κατάσταση εμφάνισης (χωρίς αλλαγή στο πραγματικό status)
    $displayStatusClass = $isCompletedQuestionnaire
        ? 'text-bg-success'
        : ($statusClass ?? \App\Support\StatusBadge::className($status ?? null));

    $displayStatusLabel = $isCompletedQuestionnaire
        ? 'Ολοκληρώθηκε'
        : ($statusLabel ?? \App\Support\StatusBadge::label($status ?? null));

    $displayBackgroundClass = $isCompletedQuestionnaire
        ? 'bg-success-subtle'
        : \App\Support\StatusBadge::backgroundClass($status ?? null);
@endphp

<div class="list-group-item border-0 mb-2 rounded-2 d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 {{ $displayBackgroundClass }}">

    <div class="flex-grow-1">

        {{-- Αριθμός και τίτλος ενέργειας --}}
        <span class="badge {{ $displayStatusClass }} me-2">{{ $number }}</span>
        <span class="fw-semibold">{{ $title }}</span>

        {{-- Υπεύθυνος ενέργειας: εμφανίζεται πάντα --}}
        <div class="small text-secondary mt-1">
            {{ $done ? 'Ολοκληρώθηκε από' : 'Ολοκληρώνεται από' }}

            <span class="badge rounded-pill bg-white text-dark border border-secondary-subtle fw-normal ms-1">
                <i class="bi {{ $roleIcons[$responsible] ?? 'bi-person' }} me-1"></i>
                {{ $responsibleLabel }}
            </span>

            @if (! $isResponsible && ! $done)
                <span class="fw-semibold ms-1">
                    Δεν είστε υπεύθυνος/η για αυτή την ενέργεια.
                </span>
            @endif
        </div>

        {{-- Μήνυμα αποκλεισμού ή αναμονής --}}
        @if ($isResponsible && ! $done && filled($blockedMessage ?? null))
            <div class="small text-danger mt-1">
                {{ $blockedMessage }}
            </div>
        @endif

       {{-- Οδηγίες για μη ολοκληρωμένες ενέργειες του τρέχοντος ρόλου --}}
@if (
    $isResponsible
    && ! $done
    && $actionHint
    && in_array($effectiveStatus, ['pending', 'draft', 'rejected'], true)
)
    <div class="small text-primary mt-1">
        <i class="bi bi-info-circle me-1"></i>
        {{ $actionHint }}
    </div>
@endif

    </div>

    {{-- Κατάσταση --}}
    <div class="d-flex align-items-center gap-2 ms-auto">
        <span class="badge {{ $displayStatusClass }}">
            {{ $displayStatusLabel }}
        </span>

        @if (filled($recordUrl ?? null))
            <a class="btn btn-sm btn-outline-primary" href="{{ $recordUrl }}">{{ $recordLabel }}</a>
        @endif
        @if (filled($pdfUrl ?? null))
            <a class="btn btn-sm btn-primary" href="{{ $pdfUrl }}" target="_blank">PDF</a>
        @endif

    </div>
</div>
