<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Πρακτική Άσκηση ΕΚΠΑ')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <header class="navbar navbar-expand-lg bg-primary" data-bs-theme="dark">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">Πρακτική Άσκηση ΕΚΠΑ</a>
        </div>
    </header>

    <main class="container py-5">
        @yield('content')
    </main>
</body>
</html>
