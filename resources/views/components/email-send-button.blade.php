@props(['student', 'email'])

<div class="d-inline-flex align-items-center gap-2" data-email-action>
    <form class="d-inline js-email-send-form" method="POST" action="{{ route('students.emails.store', [$student, $email['type']]) }}">
        @csrf
        <button class="btn btn-sm {{ $email['class'] ?? 'btn-primary' }}" type="submit" @disabled($email['unavailable_reason'])>
            {{ $email['button'] }}
        </button>
    </form>
    <span class="badge js-email-count {{ $email['count'] > 0 ? 'text-bg-success' : 'text-bg-danger' }}">({{ $email['count'] }})</span>
    <span class="small js-email-status" role="status" aria-live="polite"></span>
</div>
