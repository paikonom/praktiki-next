<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        @page { margin: 72px 42px 58px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; line-height: 1.42; color: #111; }
        header { position: fixed; top: -58px; left: 0; right: 0; text-align: center; }
        header img { max-height: 48px; max-width: 310px; }
        footer { position: fixed; bottom: -42px; left: 0; right: 0; border-top: 1px solid #aaa; padding-top: 5px; color: #666; font-size: 8px; text-align: right; }
        .page-number:after { content: counter(page); }
        h1 { margin: 8px 0 18px; font-size: 13px; line-height: 1.35; text-align: center; }
        p { margin: 0 0 8px; text-align: justify; }
        ol { margin: 8px 0 8px 20px; padding: 0; }
        li { margin-bottom: 7px; text-align: justify; }
        table { width: 100%; border-collapse: collapse; }
        .details { margin: 10px 0 12px; }
        .details td { padding: 2px 5px; vertical-align: top; }
        .details td:first-child { width: 33%; }
        .missing { color: #dc3545; }
        .signatures { margin-top: 18px; page-break-inside: avoid; }
        .signatures th, .signatures td { width: 25%; padding: 5px; text-align: center; vertical-align: top; }
        .signature-space { height: 62px; }
        .keep { page-break-inside: avoid; }
    </style>
</head>
<body>
<header>
    <img src="{{ public_path('images/190_logo_UOA_BW_cntr_GR_sites.png') }}" alt="ΕΚΠΑ">
</header>

<footer>Σελίδα <span class="page-number"></span></footer>

@yield('content')
</body>
</html>
