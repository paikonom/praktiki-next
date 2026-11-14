@extends('layouts.app')

@section('title', 'Πρόσβαση τμημάτων')

@section('content')
    <style>
        .access-hero {
            background: linear-gradient(135deg, #172554, #1d4ed8);
            border-radius: 1.25rem;
            color: white;
        }

        .access-stat {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: .9rem;
            min-width: 110px;
        }

        .access-table th {
            background: #f8fafc;
            color: #475569;
            font-size: .75rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .user-chip {
            align-items: flex-start;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: .75rem;
            display: flex;
            flex: 0 1 16rem;
            gap: .65rem;
            padding: .45rem .7rem;
        }

        .department-users {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .user-avatar {
            align-items: center;
            background: #2563eb;
            border-radius: 50%;
            color: white;
            display: inline-flex;
            font-size: .75rem;
            font-weight: 700;
            height: 2rem;
            justify-content: center;
            width: 2rem;
        }
    </style>

    @php
        $departmentCount = $departments->count();
        $activeDepartmentCount = $departments->filter(fn ($rows) => (bool) $rows->first()->active)->count();
        $accessCount = $departments->sum(fn ($rows) => $rows->whereNotNull('user_id')->count());
    @endphp

    <div class="container py-4 py-lg-5">
        <section class="access-hero p-4 p-lg-5 mb-4 shadow-sm">
            <div class="d-lg-flex align-items-center justify-content-between gap-4">
                <div>
                    <div class="small text-uppercase opacity-75 fw-semibold mb-2">Διαχείριση πρόσβασης</div>
                    <h1 class="display-6 fw-bold mb-2">Τμήματα και χρήστες</h1>
                    <p class="mb-0 opacity-75">Δείτε ποιοι χρήστες έχουν πρόσβαση σε κάθε τμήμα.</p>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4 mt-lg-0">
                    <div class="access-stat px-3 py-2 text-center">
                        <div class="fs-4 fw-bold">{{ $departmentCount }}</div>
                        <div class="small opacity-75">Τμήματα</div>
                    </div>
                    <div class="access-stat px-3 py-2 text-center">
                        <div class="fs-4 fw-bold">{{ $activeDepartmentCount }}</div>
                        <div class="small opacity-75">Ενεργά</div>
                    </div>
                    <div class="access-stat px-3 py-2 text-center">
                        <div class="fs-4 fw-bold">{{ $accessCount }}</div>
                        <div class="small opacity-75">Προσβάσεις</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="alert alert-info border-0 shadow-sm">
            <strong>Πώς διαβάζεται:</strong> κάθε χρήστης που εμφανίζεται βλέπει το τμήμα.
            Τα σήματα ΕΣΠΑ, Ίδρυμα και Φορέας δείχνουν ποιες πρακτικές μπορεί να διαχειρίζεται.
            Οι διαχειριστές έχουν καθολική πρόσβαση χωρίς να χρειάζεται να προστεθούν εδώ.
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
            <table class="table access-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-2 py-3">atlasId</th>
                        <th class="py-3">Τμήμα</th>
                        <th class="pe-4 py-3">Στοιχεία</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($departments as $departmentRows)
                        @php($department = $departmentRows->first())
                        @php($departmentUsers = $departmentRows->whereNotNull('user_id'))
                        @php($duplicateManagers = collect([
                            'ΕΣΠΑ' => $departmentUsers->where('can_manage_espa', true)->count(),
                            'Ίδρυμα' => $departmentUsers->where('can_manage_institution', true)->count(),
                            'Φορέας' => $departmentUsers->where('can_manage_internship_provider', true)->count(),
                        ])->filter(fn ($count) => $count > 1))
                        <tr>
                            <td class="px-4 py-3"><code>{{ $department->atlasId }}</code></td>
                            <td class="py-3 text-dark" style="min-width: 38rem;">
                                <div class="fw-semibold mb-2">{{ $department->tmhma }}</div>
                                @if ($departmentUsers->isNotEmpty())
                                    <div class="small fw-semibold text-muted mb-1">
                                        {{ $departmentUsers->count() }} {{ $departmentUsers->count() === 1 ? 'χρήστης' : 'χρήστες' }}
                                    </div>
                                @endif
                                @if ($duplicateManagers->isNotEmpty())
                                    <div class="alert alert-warning small py-1 px-2 mb-2">
                                        Προσοχή: περισσότεροι από έναν χειριστές για {{ $duplicateManagers->keys()->join(', ') }}.
                                    </div>
                                @endif
                                <div class="department-users">
                                    @forelse ($departmentUsers as $user)
                                        <div class="user-chip">
                                            <span class="user-avatar">{{ mb_strtoupper(mb_substr($user->user_name, 0, 1)) }}</span>
                                            <span class="flex-grow-1">
                                                <span class="d-block small fw-semibold text-dark">{{ $user->user_name }}</span>
                                                <span class="d-block text-muted mb-1" style="font-size: .75rem;">
                                                    {{ $user->username }} · {{ $user->role === 'admin' ? 'Διαχειριστής' : 'Χειριστής' }}
                                                </span>
                                                <span class="d-flex flex-wrap gap-1">
                                                    @if ($user->view_only)
                                                        <span class="badge text-bg-secondary">Μόνο προβολή</span>
                                                    @endif
                                                    @if ($user->can_manage_espa)
                                                        <span class="badge text-bg-primary">ΕΣΠΑ</span>
                                                    @endif
                                                    @if ($user->can_manage_institution)
                                                        <span class="badge text-bg-success">Ίδρυμα</span>
                                                    @endif
                                                    @if ($user->can_manage_internship_provider)
                                                        <span class="badge text-bg-warning">Φορέας</span>
                                                    @endif
                                                    @if (! $user->view_only && ! $user->can_manage_espa && ! $user->can_manage_institution && ! $user->can_manage_internship_provider)
                                                        <span class="badge text-bg-light border text-muted">Προβολή τμήματος</span>
                                                    @endif
                                                </span>
                                            </span>
                                        </div>
                                    @empty
                                        <span class="badge rounded-pill text-bg-light border text-muted">Χωρίς χρήστη</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="pe-4 py-3" style="width: 1%; white-space: nowrap;">
                                <div class="small text-muted mb-1">{{ $department->polh ?: '—' }}</div>
                                @if ($department->active)
                                    <div class="mb-2"><span class="badge rounded-pill text-bg-success">Ενεργό</span></div>
                                @else
                                    <div class="mb-2"><span class="badge rounded-pill text-bg-secondary">Ανενεργό</span></div>
                                @endif
                                <a class="btn btn-sm btn-outline-primary"
                                   href="{{ route('admin.department-access.edit', $department->department_id) }}">
                                    Διαχείριση
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
@endsection
