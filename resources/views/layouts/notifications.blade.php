
@php
    $user = auth('web')->user();

    // Εμφανίζουμε και αναγνωσμένες και μη αναγνωσμένες.
    $notifications = $user->unreadNotifications()
        ->latest()
        ->limit(10)
        ->get();

    // Το καμπανάκι μετρά μόνο τις μη αναγνωσμένες.
    $unreadCount = $user->unreadNotifications()->count();
@endphp

<style>
    .notification-item {
        transition: background-color 0.15s ease;
    }

    .notification-item.is-unread {
        background-color: #eef5ff;
    }

    .notification-item.is-unread:hover {
        background-color: #dceaff;
    }

    .notification-item.is-read {
        background-color: #ffffff;
    }

    .notification-item.is-read:hover {
        background-color: #f1f3f5;
    }

    .notification-item .notification-link {
        cursor: pointer;
    }

    .notification-item.is-read .notification-title {
        color: #6c757d;
        font-weight: 400;
    }

    .notification-item.is-read .notification-message {
        color: #868e96;
    }

    .notification-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #0d6efd;
        flex-shrink: 0;
    }
</style>

<div class="dropdown me-3">
    <button class="btn btn-outline-light btn-sm position-relative"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Ειδοποιήσεις">

        <i class="bi bi-bell-fill" aria-hidden="true"></i>

        @if ($unreadCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-0"
        data-bs-theme="light"
        style="width: 640px; max-width: 90vw; max-height: 600px; overflow-y: auto;">

        {{-- Επικεφαλίδα --}}
        <li class="px-3 py-2 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold small">Ειδοποιήσεις</span>

                <span class="badge bg-primary rounded-pill">
                    {{ $unreadCount }}
                </span>
            </div>
        </li>

        @forelse ($notifications as $notification)

            @php
                $isUnread = is_null($notification->read_at);
            @endphp

            <li class="border-bottom notification-item {{ $isUnread ? 'is-unread' : 'is-read' }}">
                <div class="px-3 py-2">

                    {{-- Σύνδεσμος ειδοποίησης --}}
                    <a href="{{ $notification->data['url'] ?? '#' }}"
                       class="notification-link text-decoration-none text-body d-block">

                        <div class="d-flex align-items-center gap-2 mb-1">

                            @if ($isUnread)
                                <span class="notification-dot"></span>
                            @endif

                            <i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }} me-1 text-primary"></i>
                            {{ $notification->data['title'] ?? 'Ειδοποίηση' }}

                                                {{-- Κουμπί αναγνώστηκε μόνο για νέες ειδοποιήσεις --}}
                    @if ($isUnread)
                        <form method="POST"
                              action="{{ route('notifications.read', $notification->id) }}"
                              class="mt-2">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="btn btn-outline-success btn-sm"
                                    style="font-size: 0.75rem;">
                                <i class="bi bi-check2-circle me-1"></i>
                                Σήμανση ως αναγνωσμένη
                            </button>
                        </form>
                    @else
                        <div class="mt-2 text-body-tertiary"
                             style="font-size: 0.75rem;">
                            <i class="bi bi-check2-all"></i>
                            Διαβάστηκε
                        </div>
                    @endif
                        </div>

                        <div class="notification-message small {{ $isUnread ? 'text-body-secondary' : '' }}">
                            {{ $notification->data['message'] ?? '' }}
                        </div>
                    </a>

                    {{-- Ημερομηνία --}}
                    <div class="text-body-tertiary mt-1"
                         style="font-size: 0.7rem;">
                        {{ $notification->created_at->diffForHumans() }}
                    </div>

                </div>
            </li>

        @empty
            <li class="px-3 py-4 text-center text-body-secondary small">
                Δεν υπάρχουν ειδοποιήσεις.
            </li>
        @endforelse
<li class="text-center border-top">
    <a href="{{ route('notifications.index') }}"
       class="dropdown-item py-3 fw-semibold text-primary">
        <i class="bi bi-list-ul me-1"></i>
        Προβολή όλων των ειδοποιήσεων
    </a>
</li>
    </ul>
    
</div>
