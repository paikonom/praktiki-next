
@extends('layouts.app')

@section('content')
<div class="d-flex gap-2 mb-4">

    <a href="{{ route('notifications.index') }}"
       class="btn {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
        Όλες
    </a>

    <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
       class="btn {{ $filter === 'unread' ? 'btn-primary' : 'btn-outline-primary' }}">
        Μη αναγνωσμένες
    </a>

    <a href="{{ route('notifications.index', ['filter' => 'read']) }}"
       class="btn {{ $filter === 'read' ? 'btn-primary' : 'btn-outline-primary' }}">
        Αναγνωσμένες
    </a>

</div>
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="bi bi-bell me-2"></i>
            Ειδοποιήσεις
        </h4>

        <span class="badge bg-primary">
            {{ $unreadCount }} μη αναγνωσμένες
        </span>
    </div>

    @forelse ($notifications as $notification)
        @php
            $isUnread = is_null($notification->read_at);
        @endphp

        <div class="card mb-2 {{ $isUnread ? 'border-primary bg-primary bg-opacity-10' : '' }}">
            <div class="card-body">

                <div class="d-flex align-items-start gap-3">
                    <i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }} fs-4 text-primary"></i>

                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <a href="{{ $notification->data['url'] ?? '#' }}"
                               class="text-decoration-none text-body fw-semibold">
                                {{ $notification->data['title'] ?? 'Ειδοποίηση' }}
                            </a>

                            @if ($isUnread)
                                <span class="badge bg-primary">Νέα</span>
                            @endif
                        </div>

                        <div class="small text-body-secondary mb-2">
                            {{ $notification->data['message'] ?? '' }}
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-body-tertiary">
                                {{ $notification->created_at->diffForHumans() }}
                            </small>

                            @if ($isUnread)
                                <form method="POST"
                                      action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit" class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-check2-circle me-1"></i>
                                        Σήμανση ως αναγνωσμένη
                                    </button>
                                </form>
                            @else
                                <small class="text-muted">
                                    <i class="bi bi-check2-all"></i>
                                    Διαβάστηκε
                                </small>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>

    @empty
        <div class="alert alert-light border text-center">
            Δεν υπάρχουν ειδοποιήσεις.
        </div>
    @endforelse

    <div class="mt-4">
        {{ $notifications->links('pagination::bootstrap-5') }}
    </div>

</div>
@endsection
