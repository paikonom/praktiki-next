@extends('layouts.app')

@section('title', 'Διαχείριση πρόσβασης')

@section('content')
    @php
        $selectedUserIds = array_map('intval', old('user_ids', $accessByUserId->keys()->all()));
        $espaUserIds = array_map('intval', old('manage_espa', $accessByUserId->where('can_manage_espa', true)->keys()->all()));
        $institutionUserIds = array_map('intval', old('manage_institution', $accessByUserId->where('can_manage_institution', true)->keys()->all()));
        $providerUserIds = array_map('intval', old('manage_internship_provider', $accessByUserId->where('can_manage_internship_provider', true)->keys()->all()));
    @endphp

    <div class="container py-4 py-lg-5" style="max-width: 900px;">
        <a href="{{ route('admin.department-access') }}" class="btn btn-sm btn-link text-decoration-none px-0 mb-3">
            ← Επιστροφή στα τμήματα
        </a>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-primary text-white border-0 p-4">
                <div class="small text-uppercase opacity-75 fw-semibold mb-2">Διαχείριση πρόσβασης</div>
                <h1 class="h3 mb-1">{{ $departmentRecord->tmhma }}</h1>
                <div class="opacity-75">atlasId: {{ $departmentRecord->atlasId }}</div>
            </div>

            <form method="POST" action="{{ route('admin.department-access.update', $departmentRecord->id) }}">
                @csrf
                @method('PUT')

                <div class="card-body p-4">
                    <p class="text-muted mb-2">Επίλεξε ποιοι χρήστες βλέπουν το τμήμα και ποιες χρηματοδοτήσεις διαχειρίζονται.</p>
                    <div class="alert alert-light border small">
                        Η επιλογή σε ΕΣΠΑ, Ίδρυμα ή Φορέας δίνει αυτόματα και πρόσβαση προβολής στο τμήμα.
                        Οι χρήστες «μόνο προβολή» δεν μπορούν να κάνουν αλλαγές, ακόμη και αν έχουν επιλεγμένη χρηματοδότηση.
                    </div>

                    @error('user_ids.*')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Χρήστης</th>
                                    <th class="text-center">Πρόσβαση<br><span class="fw-normal text-muted">βλέπει το τμήμα</span></th>
                                    <th class="text-center">Χειριστής<br><span class="fw-normal text-muted">ΕΣΠΑ</span></th>
                                    <th class="text-center">Χειριστής<br><span class="fw-normal text-muted">Ιδρύματος</span></th>
                                    <th class="text-center">Χειριστής<br><span class="fw-normal text-muted">Φορέα</span></th>
                                </tr>
                            </thead>
                            <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <span class="d-block fw-semibold">{{ $user->name }}</span>
                                    <span class="small text-muted">{{ $user->username }} · {{ $user->role }}</span>
                                    @if ($user->role === 'admin')
                                        <span class="badge text-bg-primary ms-1">Καθολική πρόσβαση</span>
                                    @elseif ($user->view_only)
                                        <span class="badge text-bg-secondary ms-1">Μόνο προβολή</span>
                                    @endif
                                </td>
                                <td class="text-center"><input class="form-check-input" type="checkbox" name="user_ids[]" value="{{ $user->id }}" @checked(in_array($user->id, $selectedUserIds, true))></td>
                                <td class="text-center"><input class="form-check-input" type="checkbox" name="manage_espa[]" value="{{ $user->id }}" @checked(in_array($user->id, $espaUserIds, true))></td>
                                <td class="text-center"><input class="form-check-input" type="checkbox" name="manage_institution[]" value="{{ $user->id }}" @checked(in_array($user->id, $institutionUserIds, true))></td>
                                <td class="text-center"><input class="form-check-input" type="checkbox" name="manage_internship_provider[]" value="{{ $user->id }}" @checked(in_array($user->id, $providerUserIds, true))></td>
                            </tr>
                        @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white border-top p-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.department-access') }}" class="btn btn-light">Ακύρωση</a>
                    <button type="submit" class="btn btn-primary">Αποθήκευση</button>
                </div>
            </form>
        </div>
    </div>
@endsection
