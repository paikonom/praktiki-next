@extends('layouts.app')

@section('title', 'Πεδία καρτέλας')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Πεδία καρτέλας</h1>
            <p class="text-body-secondary mb-0">Ονόματα, οδηγίες, ενότητες και σειρά εμφάνισης στις φόρμες φοιτητή και φορέα.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @foreach ($fieldGroups as $cardSection => $fields)
        <section class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">{{ ['student' => 'Φοιτητής', 'provider' => 'Φορέας', 'office' => 'Γραφείο'][$cardSection] ?? $cardSection }}</h2>
                <span class="badge text-bg-secondary">{{ $fields->count() }} πεδία</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Κλειδί</th><th>Όνομα πεδίου</th><th>Ενότητα</th><th>Χρηματοδότηση</th><th>Προβολή</th><th>Οδηγία</th><th>Σειρά</th><th>Κατάσταση</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($fields as $field)
                            <tr>
                                <td><code>{{ $field->field }}</code></td>
                                <td>{{ $field->label }}</td>
                                <td>{{ $field->section ?: '—' }}</td>
                                <td>{{ implode(', ', $field->fundings ?? []) }}</td>
                                <td>{{ implode(', ', $field->visible_to_roles ?? []) }}</td>
                                <td>{{ filled($field->help_text) ? 'Ναι' : '—' }}</td>
                                <td>{{ $field->sort_order }}</td>
                                <td><span class="badge {{ $field->active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $field->active ? 'Ενεργό' : 'Ανενεργό' }}</span></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.student-card-fields.edit', $field) }}" data-scroll-return>Επεξεργασία</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
@endsection
