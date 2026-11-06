<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h2 class="h5 mb-1">Emails / Log</h2>
        <div class="small text-muted">Προεπισκόπηση, χειροκίνητη αποστολή και πλήθος επιτυχημένων αποστολών.</div>
    </div>
    <div class="list-group list-group-flush">
        @foreach ($emailEntries as $email)
            <div class="list-group-item py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="{{ $email['unavailable_reason'] ? 'text-danger' : '' }}">{{ $email['title'] }}</div>
                    <div class="d-flex align-items-center gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('students.emails.preview', [$student, $email['type']]) }}" title="Προεπισκόπηση">👁</a>
                        <x-email-send-button :student="$student" :email="$email" />
                    </div>
                </div>
                @if ($email['unavailable_reason'])
                    <div class="small text-danger mt-1">{{ $email['unavailable_reason'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
</div>

@if ($student->emailLogs->isNotEmpty())
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Ιστορικό αποστολών</h2></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Ημερομηνία</th><th>Τύπος</th><th>Παραλήπτες</th><th>Χειριστής</th><th>Κατάσταση</th></tr></thead>
                <tbody>
                    @foreach ($student->emailLogs->sortByDesc('created_at') as $log)
                        <tr>
                            <td class="text-nowrap">{{ ($log->sent_at ?? $log->created_at)?->format('d/m/Y H:i') ?? 'Άγνωστη ημερομηνία' }}</td>
                            <td>{{ $log->subject }}</td>
                            <td>{{ implode(', ', $log->to) }}</td>
                            <td>{{ $log->sentBy?->name ?? '—' }}</td>
                            <td><span class="badge {{ $log->status === 'sent' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $log->status === 'sent' ? 'Στάλθηκε' : 'Απέτυχε' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
