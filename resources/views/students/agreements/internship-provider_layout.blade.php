
<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">

    <title>@yield('title')</title>

    <style>
        @page {
            size: A4;
            margin: 45px 55px 65px 55px;
        }

        body {
            font-family: "DejaVu Serif", serif;
            font-size: 11px;
            line-height: 1.2;
            color: #000;
        }

        .provider-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            text-align: center;
        }

        .provider-header td {
            text-align: center;
            vertical-align: middle;
            padding: 0;
        }

        .provider-header img {
            width: 300px;
            height: 100px;
        }

        h1 {
            font-size: 11pt;
            line-height: 1.25;
            font-weight: bold;
            text-align: center;
            margin: 12px 0 22px;
        }

        p {
            text-align: justify;
            margin: 0 0 10px;
        }

        ol {
            margin: 8px 0 10px 22px;
            padding: 0;
        }

        li {
            text-align: justify;
            margin-bottom: 8px;
        }

        /* Πίνακας φοιτητή έξι στηλών */

        .student-details {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 11px;
            margin: 10px 0 12px;
        }

        .student-details td {
            padding: 2px 3px;
            vertical-align: top;
            overflow-wrap: break-word;
        }

        .student-details td:nth-child(2),
        .student-details td:nth-child(5) {
            text-align: center;
        }

        .student-details td:nth-child(3),
        .student-details td:nth-child(6) {
            font-weight: bold;
        }

        /* Υπογραφές */

        .signatures {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: avoid;
        }

        .signatures th,
        .signatures td {
            text-align: center;
            vertical-align: top;
            padding: 5px 4px;
            overflow-wrap: break-word;
        }

        .signatures th {
            font-weight: bold;
        }

        .signatures .signature-space td {
            height: 65px;
        }

        /* Υποσημειώσεις μετά τις υπογραφές */

        .footnote {
            font-size: 6pt;
            line-height: 1.15;
            text-align: justify;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 12px;
        }

        .missing {
            color: red;
            font-weight: bold;
        }

        .keep {
            page-break-inside: avoid;
        }

        /* Αρίθμηση σελίδων */

        .page-footer {
            position: fixed;
            bottom: -38px;
            right: 0;
            font-size: 9pt;
            color: grey;
            text-align: right;
        }

        .page-number:before {
            content: counter(page);
        }

        .page-total:before {
            content: counter(pages);
        }
    </style>
</head>

<body>

<table class="provider-header">
    <tr>
        <td>
            <img
                src="{{ public_path('images/ekpa_log_with_text.jpg') }}"
                alt="Εθνικό και Καποδιστριακό Πανεπιστήμιο Αθηνών"
            >
        </td>
    </tr>
</table>

<div class="page-footer">
    Σελίδα <span class="page-number"></span>
    από <span class="page-total"></span>
</div>

@yield('content')

</body>
</html>
