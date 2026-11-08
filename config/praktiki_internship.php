<?php

return [
    'cycle_fields' => [
        'Espa' => [
            'ep_yp_ergou' => ['label' => 'Επιστημονικός Υπεύθυνος έργου', 'max' => 255],
            'ep_yp_ergou_title' => ['label' => 'Ιδιότητα επιστημονικού υπεύθυνου έργου', 'max' => 255],
            'K_E_ergou' => ['label' => 'Κ.Ε. έργου', 'max' => 50],
            'titlos_praxhs' => ['label' => 'Τίτλος Πράξης', 'max' => 10000, 'multiline' => true],
            'titlos_programmatos' => ['label' => 'Τίτλος Προγράμματος', 'max' => 10000, 'multiline' => true],
            'titlos_ypoergou' => ['label' => 'Τίτλος Υποέργου', 'max' => 10000, 'multiline' => true],
            'kwdikos_OPS' => ['label' => 'Κωδικός ΟΠΣ / MIS', 'max' => 50],
            'scientific_supervisor_a_a_prosklhsh' => ['label' => 'Α/Α Πρόσκλησης Υπ. Τμήματος', 'max' => 50],
            'scientific_supervisor_OPS_prosklhsh' => ['label' => 'ΟΠΣ Πρόσκλησης Υπ. Τμήματος', 'max' => 50],
            'kostos_apof_a_a' => ['label' => 'Α/Α Απόφασης Ανάληψης Υποχρέωσης', 'max' => 50],
            'kostos_apof_ADA' => ['label' => 'ΑΔΑ Απόφασης Ανάληψης Υποχρέωσης', 'max' => 50],
            'legal_representative' => ['label' => 'Νόμιμος εκπρόσωπος ΕΛΚΕ', 'max' => 255],
            'legal_representative_title' => ['label' => 'Ιδιότητα νόμιμου εκπροσώπου ΕΛΚΕ', 'max' => 255],
        ],
        'Institution' => [
            'ep_yp_ergou' => ['label' => 'Ιδρυματικός Υπεύθυνος', 'max' => 255],
            'ep_yp_ergou_title' => ['label' => 'Ιδιότητα επιστημονικού υπεύθυνου έργου', 'max' => 255],
            'K_E_ergou' => ['label' => 'Κ.Ε. ΕΛΚΕ', 'max' => 50],
            'legal_representative' => ['label' => 'Νόμιμος εκπρόσωπος ΕΛΚΕ', 'max' => 255],
            'legal_representative_title' => ['label' => 'Ιδιότητα νόμιμου εκπροσώπου ΕΛΚΕ', 'max' => 255],
        ],
        'InternshipProvider' => [],
    ],

    'phase_fields' => [
        'Espa' => [
            'apofash_tmhmatos' => ['label' => 'Απόφαση Τμήματος', 'max' => 200],
            'apofash_ELKE' => ['label' => 'Απόφαση ΕΛΚΕ', 'max' => 50],
            'ADA_apofash_ELKE' => ['label' => 'ΑΔΑ Απόφασης ΕΛΚΕ', 'max' => 50],
        ],
        'Institution' => [
            'apofash_ELKE' => ['label' => 'Εγκριτική απόφαση ΕΛΚΕ', 'max' => 50],
        ],
        'InternshipProvider' => [
            'apofash_tmhmatos' => ['label' => 'Απόφαση Τμήματος (συνέλευση)', 'max' => 200],
        ],
    ],

    'phase_urls' => [
        'url_prosklhsh' => ['label' => 'URL Πρόσκλησης', 'button_label' => 'ΠΡΟΣΚΛΗΣΗ'],
        'url_paratash' => ['label' => 'URL Παράτασης', 'button_label' => 'ΠΑΡΑΤΑΣΗ'],
        'url_prosorina' => ['label' => 'URL Προσωρινών', 'button_label' => 'ΠΡΟΣΩΡΙΝΑ'],
        'url_enstaseis' => ['label' => 'URL Ενστάσεων', 'button_label' => 'ΕΝΣΤΑΣΕΙΣ'],
        'url_telika' => ['label' => 'URL Τελικών', 'button_label' => 'ΤΕΛΙΚΑ'],
        'url_syneleysh' => ['label' => 'URL Γενικής Συνέλευσης', 'button_label' => 'ΓΕΝΙΚΗ ΣΥΝΕΛΕΥΣΗ'],
    ],
];
