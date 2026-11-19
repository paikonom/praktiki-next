@extends('layouts.app')

@section('title', 'Ενιαίο PDF εντύπων')

@section('content')
<div class="d-flex justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Ενιαίο PDF εντύπων</h1>
        <div class="text-muted">{{ $student->full_name }} · ΑΜ: {{ $student->AMF }}</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('students.show', ['student' => $student, 'panel' => 'documents']) }}">Επιστροφή στα έντυπα</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        Θα χρησιμοποιηθεί μόνο η τελευταία εγκεκριμένη έκδοση κάθε εντύπου.
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Έντυπο</th><th>Αρχείο</th><th>Σελίδες</th><th>Κατάσταση</th></tr></thead>
            <tbody>
            @foreach ($files as $file)
                <tr>
                    <td>{{ $file['title'] }}</td>
                    <td>{{ $file['document']?->original_name ?? '—' }}</td>
                    <td>{{ $file['pages'] ?? '—' }}</td>
                    <td>
                        @if ($file['reason'])
                            <span class="text-danger"><i class="bi bi-x-circle me-1"></i>{{ $file['reason'] }}</span>
                        @else
                            <span class="text-success"><i class="bi bi-check-circle me-1"></i>Θα συμπεριληφθεί</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end">
        <form method="POST" action="{{ route('students.documents.merge.download', $student) }}">
            @csrf
            <button class="btn btn-primary" @disabled($files->whereNull('reason')->isEmpty())>
                <i class="bi bi-file-earmark-pdf me-1"></i>Δημιουργία και λήψη PDF
            </button>
        </form>
    </div>
</div>
@endsection
