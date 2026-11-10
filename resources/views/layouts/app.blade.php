<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Πρακτική Άσκηση ΕΚΠΑ')</title>

    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}" defer></script>
    <script src="{{ asset('js/email-actions.js') }}" defer></script>
    <script src="{{ asset('js/scroll-restore.js') }}?v=3" defer></script>
    <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">
    <header class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ auth('web')->check() ? route('dashboard') : (auth('student')->check() ? route('student.dashboard') : (auth('provider')->check() ? route('provider.dashboard') : (auth('scientific_supervisor')->check() ? route('scientific_supervisor.dashboard') : route('home')))) }}">
                <img src="{{ asset('images/190_logo_UOA_BW_cntr_GR_sites.png') }}" width="105" height="56" class="bg-white rounded p-1" alt="Εθνικό και Καποδιστριακό Πανεπιστήμιο Αθηνών">
                <span class="d-none d-md-inline">Πρακτική Άσκηση ΕΚΠΑ</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainMenu" aria-controls="mainMenu" aria-expanded="false" aria-label="Εμφάνιση μενού">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainMenu">
                @auth('web')
                    <nav class="navbar-nav me-auto">
                        <a class="nav-link @if (request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                            Αρχική
                        </a>
                        <a class="nav-link @if (request()->routeIs('praktikes.*')) active @endif" href="{{ route('praktikes.index') }}" @if (request()->routeIs('praktikes.*')) aria-current="page" @endif>
                            Πρακτικές
                        </a>
                        <a class="nav-link @if (request()->routeIs('faseis.*')) active @endif" href="{{ route('faseis.index') }}" @if (request()->routeIs('faseis.*')) aria-current="page" @endif>
                            Φάσεις
                        </a>
                        <a class="nav-link @if (request()->routeIs('students.*')) active @endif" href="{{ route('students.index') }}" @if (request()->routeIs('students.*')) aria-current="page" @endif>
                            Φοιτητές
                        </a>
                        @can('view-admin')
                            @php($adminMenuActive = request()->routeIs('admin.*'))
                            <div class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle @if ($adminMenuActive) active @endif" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Διαχείριση
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item @if (request()->routeIs('admin.department-access*')) active @endif" href="{{ route('admin.department-access') }}">Χρήστες και τμήματα</a>
                                    <a class="dropdown-item @if (request()->routeIs('admin.document-types.*')) active @endif" href="{{ route('admin.document-types.index') }}">Τύποι εγγράφων</a>
                                    <a class="dropdown-item @if (request()->routeIs('admin.student-card-fields.*')) active @endif" href="{{ route('admin.student-card-fields.index') }}">Πεδία καρτέλας</a>
                                    <a class="dropdown-item @if (request()->routeIs('admin.scientific-supervisors.*')) active @endif" href="{{ route('admin.scientific-supervisors.index') }}">Υπεύθυνοι Π.Τ.</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item @if (request()->routeIs('admin.workflow-actions.*')) active @endif" href="{{ route('admin.workflow-actions.index') }}">Ενέργειες ροής</a>
                                    <a class="dropdown-item @if (request()->routeIs('admin.workflow-section-rules.*')) active @endif" href="{{ route('admin.workflow-section-rules.index') }}">Κανόνες ενοτήτων</a>
                                </div>
                            </div>
                        @endcan
                    </nav>

                    @include('layouts.notifications')

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-outline-light btn-sm" type="submit">Αποσύνδεση</button>
                    </form>
                @else
                    @auth('student')
                        <div class="d-flex align-items-center gap-3 text-white ms-auto">
                            <span class="small">{{ auth('student')->user()->full_name ?: 'sid: '.auth('student')->id() }}</span>
                            <form method="POST" action="{{ route('student.logout') }}">
                                @csrf
                                <button class="btn btn-outline-light btn-sm" type="submit">Αποσύνδεση</button>
                            </form>
                        </div>
                    @else
                        @auth('provider')
                            <div class="d-flex align-items-center gap-3 text-white ms-auto">
                                <span class="small">Φορέας για: {{ auth('provider')->user()->full_name }}</span>
                                <form method="POST" action="{{ route('provider.logout') }}">
                                    @csrf
                                    <button class="btn btn-outline-light btn-sm" type="submit">Αποσύνδεση</button>
                                </form>
                            </div>
                        @else
                            @auth('scientific_supervisor')
                                <nav class="navbar-nav me-auto">
                                    <a class="nav-link active" href="{{ route('scientific_supervisor.dashboard') }}">Οι φοιτητές μου</a>
                                    <span class="nav-link disabled">Αλλαγή κωδικού — σύντομα</span>
                                </nav>
                                <div class="d-flex align-items-center gap-3 text-white"><span class="small">{{ auth('scientific_supervisor')->user()->full_name }}</span><form method="POST" action="{{ route('scientific_supervisor.logout') }}">@csrf<button class="btn btn-outline-light btn-sm">Αποσύνδεση</button></form></div>
                            @else
                                <div class="ms-auto"><a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">Σύνδεση χειριστή</a></div>
                            @endauth
                        @endauth
                    @endauth
                @endauth
            </div>
        </div>
    </header>

    <main class="container py-5 flex-grow-1">
        @if (session('status'))
            <div class="position-fixed end-0 p-3" style="top: 4.5rem; z-index: 1080; width: min(26rem, 100vw);">
                <div class="alert alert-{{ session('status_type', 'success') }} alert-dismissible fade show shadow" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Κλείσιμο"></button>
                </div>
            </div>
        @endif
        @yield('content')
    </main>
    @yield('footer')
    @stack('scripts')
</body>
</html>
