
<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>

    <style>
        @page {
            size: A4;
            margin: 65px 57px 115px;
        }

        body {
            font-family: "DejaVu Serif", serif;
            font-size: 11px;
            line-height: normal;
            color: #000;
        }

        /* Κωδικός συμφωνητικού, επάνω δεξιά */
        .document-code {
            position: fixed;
            top: -48px;
            right: 0;
            color: grey;
            font-weight: bold;
            font-size: 11pt;
            text-align: right;
        }

        /* Κεφαλίδα ΕΚΠΑ - εμφανίζεται στην αρχή */
        .espa-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .espa-header td {
            vertical-align: middle;
            text-align: center;
        }

        .espa-header .flag {
            width: 15%;
        }

        .espa-header .heading {
            width: 70%;
            font-size: 10pt;
            font-weight: bold;
            line-height: 1.2;
        }

        .espa-header img {
            width: 100px;
            height: 70px;
        }

        /* Τίτλος */
        h1 {
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            line-height: normal;
            margin: 18px 0 22px;
        }

        /* Κείμενο */
        p {
            margin: 0 0 9px;
            text-align: justify;
        }

        ol {
            margin: 8px 0 8px 22px;
            padding: 0;
        }

        li {
            margin-bottom: 7px;
            text-align: justify;
        }

        /* Στοιχεία φοιτητή */
        .details {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px;
            font-size: 11px;
        }

        .details td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .details td:first-child {
            width: 35%;
        }

        .details td:last-child {
            font-weight: bold;
        }

        /* Ελλιπή στοιχεία */
        .missing {
            color: red;
            font-weight: bold;
        }

        /* Υπογραφές */
        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .signatures th,
        .signatures td {
            padding: 6px;
            text-align: center;
            vertical-align: top;
        }

        .signatures th {
            font-weight: bold;
        }

        .signature-space {
            height: 55px;
        }

        /* Υποσημειώσεις */
        .footnote {
            font-size: 6pt;
            margin-top: 12px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        /* Λογότυπα χρηματοδότησης */
        .espa-footer {
            position: fixed;
            bottom: -100px;
            left: 0;
            right: 0;
        }

        .espa-footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .espa-footer td:first-child {
            width: 70%;
            text-align: left;
        }

        .espa-footer td:last-child {
            width: 30%;
            text-align: right;
        }

        .espa-footer .funding-logo {
            width: 70%;
        }

        .espa-footer .quality-logo {
            width: 20%;
        }

        .keep {
            page-break-inside: avoid;
        }
        .st_details td:first-child {
    width: 37%;
    font-weight: normal;
}

.st_details td:nth-child(2) {
    width: 3%;
    text-align: center;
    font-weight: normal;
}

.st_details td:nth-child(3) {
    width: 60%;
    font-weight: bold;
}

.st_details td {
    padding: 1px 3px;
    vertical-align: top;
}

.signatures tr.no-top-padding td,
.signatures tr.no-top-padding th {
    padding-top: 0 !important;
}
    </style>
</head>

<body>

<div class="document-code">
    Ε-ΔΠ-06-Ε38.V9
</div>

<table class="espa-header">
    <tr>
        <td class="flag">
            <img
                src="{{ public_path('images/greece.png') }}"
                alt="Ελληνική σημαία"
            >
        </td>
        <td class="heading">
            ΕΘΝΙΚΟ ΚΑΙ ΚΑΠΟΔΙΣΤΡΙΑΚΟ ΠΑΝΕΠΙΣΤΗΜΙΟ ΑΘΗΝΩΝ<br>
            ΕΙΔΙΚΟΣ ΛΟΓΑΡΙΑΣΜΟΣ ΚΟΝΔΥΛΙΩΝ ΕΡΕΥΝΑΣ<br>
            Μονάδα Οικονομικής και Διοικητικής Υποστήριξης
        </td>
        <td class="flag">
            <img
                src="{{ public_path('images/eu.png') }}"
                alt="Ευρωπαϊκή Ένωση"
            >
        </td>
    </tr>
</table>

<div class="espa-footer">
    <table>
        <tr>
            <td>
                <img
                    class="funding-logo"
                    src="{{ public_path('images/epsa-koinwnikh-synoxh-2021-2027.jpg') }}"
                    alt="Πρόγραμμα χρηματοδότησης"
                >
            </td>
            <td>
                <img
                    class="quality-logo"
                    src="{{ public_path('images/dqs.png') }}"
                    alt="DQS"
                >
            </td>
        </tr>
    </table>
</div>

@yield('content')

</body>
</html>
