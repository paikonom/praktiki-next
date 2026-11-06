@php
    $currentRole ??= 'student';
    $showActionControls ??= true;
    $responsible ??= $currentRole;
    $isResponsible = $responsible === $currentRole;
    $responsibleLabel = match ($responsible) {
        'student' => 'Φοιτητής/τρια',
        'provider' => 'Φορέας υποδοχής',
        'scientific_supervisor' => 'ΕΠ/ΥΠ',
        'member' => 'Γραφείο Πρακτικής Άσκησης',
        default => $responsible,
    };
@endphp

<div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 {{ $isResponsible ? '' : 'bg-secondary-subtle' }}">
    <div class="flex-grow-1">
        <span class="badge text-bg-secondary me-2">{{ $number }}</span>
        <span class="fw-semibold">{{ $title }}</span>
        <span class="badge rounded-pill text-bg-light border text-dark ms-2">Υπεύθυνος: {{ $responsibleLabel }}</span>
        @if (! $isResponsible)
            <div class="small text-secondary mt-1">Δεν είστε υπεύθυνος/η για αυτή την ενέργεια.</div>
        @elseif (! $done && filled($blockedMessage ?? null))
            <div class="small text-danger mt-1">{{ $blockedMessage }}</div>
        @endif
        @if ($done && filled($doneMessage ?? null))
            <div class="small text-success mt-1">{{ $doneMessage }}</div>
        @endif
    </div>
    <div class="d-flex align-items-center gap-2 ms-auto">
        @if ($showActionControls)
            @if (filled($disabledSecondaryLabel ?? null))
                <button class="btn btn-outline-secondary btn-sm" type="button" disabled>{{ $disabledSecondaryLabel }}</button>
            @endif
            @if ($done && isset($doneUrl))
                <a class="btn btn-outline-primary btn-sm" href="{{ $doneUrl }}">{{ $doneActionLabel ?? 'Προβολή' }}</a>
            @elseif ($isResponsible && ($available ?? false) && filled($formAction ?? null))
                <form method="POST" action="{{ $formAction }}" class="d-flex gap-2">
                    @csrf
                    <select class="form-select form-select-sm" name="status" required>
                        @foreach ($allowedStatuses as $allowedStatus)
                            <option value="{{ $allowedStatus }}" @selected($allowedStatus === $status)>{{ $allowedStatus }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary btn-sm">Αποθήκευση</button>
                </form>
            @elseif ($isResponsible && ($available ?? false) && isset($url))
                <a class="btn btn-primary btn-sm" href="{{ $url }}">{{ $actionLabel ?? 'Συμπλήρωση' }}</a>
            @elseif (filled($disabledActionLabel ?? null))
                <button class="btn btn-outline-secondary btn-sm" type="button" disabled>{{ $disabledActionLabel }}</button>
            @endif
        @endif

        <span class="badge {{ $statusClass ?? ($done ? 'text-bg-success' : 'text-bg-danger') }}">
            {{ $statusLabel ?? ($done ? 'Ολοκληρωμένη' : 'Μη ολοκληρωμένη') }}
        </span>
    </div>
</div>
