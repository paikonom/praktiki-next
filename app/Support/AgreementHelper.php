<?php

namespace App\Support;

class AgreementHelper
{
    public static function amountToGreekWords($amount): ?string
    {
        if (blank($amount)) {
            return null;
        }

        $words = [
            0 => 'ΜΗΔΕΝ', 1 => 'ΕΝΑ', 2 => 'ΔΥΟ',
            3 => 'ΤΡΙΑ', 4 => 'ΤΕΣΣΕΡΑ', 5 => 'ΠΕΝΤΕ',
            6 => 'ΕΞΙ', 7 => 'ΕΠΤΑ', 8 => 'ΟΚΤΩ',
            9 => 'ΕΝΝΕΑ', 10 => 'ΔΕΚΑ', 11 => 'ΕΝΤΕΚΑ',
            12 => 'ΔΩΔΕΚΑ', 13 => 'ΔΕΚΑΤΡΙΑ',
            14 => 'ΔΕΚΑΤΕΣΣΕΡΑ', 15 => 'ΔΕΚΑΠΕΝΤΕ',
            16 => 'ΔΕΚΑΕΞΙ', 17 => 'ΔΕΚΑΕΠΤΑ',
            18 => 'ΔΕΚΑΟΚΤΩ', 19 => 'ΔΕΚΑΕΝΝΕΑ',
            20 => 'ΕΙΚΟΣΙ', 30 => 'ΤΡΙΑΝΤΑ',
            40 => 'ΣΑΡΑΝΤΑ', 50 => 'ΠΕΝΗΝΤΑ',
            60 => 'ΕΞΗΝΤΑ', 70 => 'ΕΒΔΟΜΗΝΤΑ',
            80 => 'ΟΓΔΟΝΤΑ', 90 => 'ΕΝΕΝΗΝΤΑ',
            100 => 'ΕΚΑΤΟΝ', 200 => 'ΔΙΑΚΟΣΙΑ',
            300 => 'ΤΡΙΑΚΟΣΙΑ', 400 => 'ΤΕΤΡΑΚΟΣΙΑ',
            500 => 'ΠΕΝΤΑΚΟΣΙΑ', 600 => 'ΕΞΑΚΟΣΙΑ',
            700 => 'ΕΠΤΑΚΟΣΙΑ', 800 => 'ΟΚΤΑΚΟΣΙΑ',
            900 => 'ΕΝΝΙΑΚΟΣΙΑ',
        ];

        $convert = function (int $n) use (&$convert, $words): string {
            if ($n < 20) {
                return $words[$n];
            }

            if ($n < 100) {
                $t = intdiv($n, 10) * 10;
                return $words[$t] .
                    ($n % 10 ? ' ' . $convert($n % 10) : '');
            }

            if ($n < 1000) {
                $h = intdiv($n, 100) * 100;
                return $words[$h] .
                    ($n % 100 ? ' ' . $convert($n % 100) : '');
            }

            if ($n < 1000000) {
                $th = intdiv($n, 1000);
                $rest = $n % 1000;

                $prefix = $th === 1
                    ? 'ΧΙΛΙΑ'
                    : $convert($th) . ' ΧΙΛΙΑΔΕΣ';

                return $prefix .
                    ($rest ? ' ' . $convert($rest) : '');
            }

            return (string) $n;
        };

        $totalCents = (int) round((float) $amount * 100);
        $euros = intdiv($totalCents, 100);
        $cents = $totalCents % 100;

        $result = $convert($euros) . ' ΕΥΡΩ';

        if ($cents > 0) {
            $result .= ' ΚΑΙ ' . $convert($cents) . ' ΛΕΠΤΑ';
        }

        return $result;
    }
}
