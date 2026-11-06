@props(['id', 'title', 'done'])

<div class="accordion-item mb-3 shadow-sm">
    <h2 class="accordion-header">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#provider-workflow-{{ $id }}">
            <span class="fw-semibold me-2">{{ $title }}</span>
            <span class="badge {{ $done ? 'text-bg-success' : 'text-bg-danger' }}">{{ $done ? 'Ολοκληρωμένη' : 'Μη ολοκληρωμένη' }}</span>
        </button>
    </h2>
    <div id="provider-workflow-{{ $id }}" class="accordion-collapse collapse" data-bs-parent="#provider-workflow">
        <div class="list-group list-group-flush">{{ $slot }}</div>
    </div>
</div>
