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
            align-items: center;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: .75rem;
            display: inline-flex;
            gap: .65rem;
            margin: .2rem .25rem .2rem 0;
            padding: .45rem .7rem;
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

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
            <table class="table access-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4 py-3">atlasId</th>
                        <th class="py-3">Τμήμα</th>
                        <th class="py-3">Πόλη</th>
                        <th class="py-3">Κατάσταση</th>
                        <th class="pe-4 py-3">Χρήστες</th>
                        <th class="pe-4 py-3 text-end">Ενέργεια</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($departments as $departmentRows)
                        @php($department = $departmentRows->first())
                        <tr>
                            <td class="px-4 py-3"><code>{{ $department->atlasId }}</code></td>
                            <td class="py-3 fw-semibold text-dark">{{ $department->tmhma }}</td>
                            <td class="py-3 text-muted">{{ $department->polh ?: '—' }}</td>
                            <td class="py-3">
                                @if ($department->active)
                                    <span class="badge rounded-pill text-bg-success">Ενεργό</span>
                                @else
                                    <span class="badge rounded-pill text-bg-secondary">Ανενεργό</span>
                                @endif
                            </td>
                            <td class="pe-4 py-3">
                                @forelse ($departmentRows->whereNotNull('user_id') as $user)
                                    <div class="user-chip">
                                        <span class="user-avatar">{{ mb_strtoupper(mb_substr($user->user_name, 0, 1)) }}</span>
                                        <span>
                                            <span class="d-block small fw-semibold text-dark">{{ $user->user_name }}</span>
                                            <!-- <span class="d-block text-muted" style="font-size: .75rem;">{{ $user->username }} · {{ $user->role }}</span> -->
                                        </span>
                                    </div>
                                @empty
                                    <span class="badge rounded-pill text-bg-light border text-muted">Χωρίς χρήστη</span>
                                @endforelse
                            </td>
                            <td class="pe-4 py-3 text-end">
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
