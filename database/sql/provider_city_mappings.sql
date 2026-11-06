UPDATE providers
SET f_polh_rescom = CASE TRIM(f_perioxh)
    WHEN 'ΑΘΗΝΑ' THEN 15875
    WHEN 'ΠΕΙΡΑΙΑΣ' THEN 16438
    WHEN 'ΜΑΡΟΥΣΙ' THEN 15915
    WHEN 'ΧΑΪΔΑΡΙ' THEN 16016
    WHEN 'ΑΘΗΝΑ - ΑΤΤΙΚΗ' THEN 15875
    WHEN 'ΜΑΡΟΥΣΙ ΑΤΤΙΚΗΣ' THEN 15915
    WHEN 'ΑΘΗΝΑ-ΜΑΡΟΥΣΙ' THEN 15915
    WHEN 'ΑΘΗΝΑ, ΑΤΤΙΚΗ' THEN 15875
    WHEN 'ΚΗΦΙΣΙΑ' THEN 15931
    WHEN 'ΚΟΡΩΠΙ' THEN 16105
    WHEN 'ΧΑΛΑΝΔΡΙ' THEN 15984
    WHEN 'ΑΘΗΝΑ-ΑΤΤΙΚΗ' THEN 15875
    WHEN 'ΝΕΑ ΣΜΥΡΝΗ' THEN 16052
    WHEN 'ΠΑΛΑΙΟ ΦΑΛΗΡΟ' THEN 16056
    WHEN 'ΧΑΛΚΙΔΑ' THEN 8437
    WHEN 'ΑΘΗΝΑ - ΜΑΡΟΥΣΙ' THEN 15915
    WHEN 'ΑΘΗΝΑ - ΧΑΛΑΝΔΡΙ' THEN 15984
    WHEN 'ΑΙΓΑΛΕΩ' THEN 16004
    WHEN 'ΧΙΟΣ' THEN 17398
    WHEN 'ΑΘΗΝΑ-ΝΕΑ ΣΜΥΡΝΗ' THEN 16052
    WHEN 'ΠΑΤΡΑ' THEN 10594
    WHEN 'ΧΑΪΔΑΡΙ ΑΤΤΙΚΗΣ' THEN 16016
    WHEN 'ΑΓΙΑ ΠΑΡΑΣΚΕΥΗ' THEN 16200
    WHEN 'ΑΘΗΝΑ - ΝΕΑ ΣΜΥΡΝΗ' THEN 16052
    WHEN 'ΑΘΗΝΑ ΑΤΤΙΚΗΣ' THEN 15875
    WHEN 'ΑΘΗΝΑ ΜΑΡΟΥΣΙ' THEN 15915
    WHEN 'ΑΘΗΝΑ- ΑΤΤΙΚΗ' THEN 15875
    WHEN 'ΑΘΗΝΑ-ΓΑΛΑΤΣΙ' THEN 15883
    WHEN 'ΑΘΗΝΑ-ΧΑΪΔΑΡΙ' THEN 16016
    WHEN 'ΑΧΑΡΝΑΊ' THEN 16061
    WHEN 'ΗΡΑΚΛΕΙΟ ΑΤΤΙΚΗΣ' THEN 15927
    WHEN 'ΙΩΑΝΝΙΝΑ' THEN 4198
    WHEN 'ΜΑΓΟΥΛΑ ΑΤΤΙΚΗΣ' THEN 16360
    WHEN 'ΝΈΑ ΣΜΥΡΝΗ ΑΤΤΙΚΗΣ' THEN 16052
    WHEN 'ΠΕΤΡΟΥΠΟΛΗ' THEN 16012
    WHEN 'ΑΓΙΟΙ ΑΝΑΡΓΥΡΟΙ' THEN 16326
    WHEN 'ΑΓΙΟΣ ΔΗΜΗΤΡΙΟΣ ΑΤΤΙΚΗΣ' THEN 16026
    WHEN 'ΆΓΙΟΣ ΙΩΆΝΝΗΣ ΡΈΝΤΗΣ' THEN 16457
    WHEN 'ΑΘΉΝΑ - ΑΤΤΙΚΉΣ' THEN 15875
    WHEN 'ΑΘΗΝΑ - ΚΗΦΙΣΙΑ' THEN 15931
    WHEN 'ΑΘΗΝΑ - ΚΟΥΚΑΚΙ' THEN 15875
    WHEN 'ΑΘΗΝΑ - ΝΕΑ ΠΕΝΤΕΛΗ' THEN 15966
    WHEN 'ΑΘΗΝΑ ΑΤΤΙΚΗ' THEN 15875
    WHEN 'ΑΘΗΝΑ ΠΕΙΡΑΙΑΣ' THEN 16438
    WHEN 'ΑΘΗΝΑ-ΒΥΡΩΝΑΣ' THEN 15879
    WHEN 'ΑΘΗΝΑ-ΧΑΛΑΝΔΡΙ' THEN 15984
    WHEN 'ΑΘΗΝΑ-ΧΟΛΑΡΓΟΣ' THEN 15956
    WHEN 'ΑΘΗΝΑ, ΧΑΛΑΝΔΡΙ' THEN 15984
    WHEN 'ΒΎΡΩΝΑΣ ΑΘΉΝΑ' THEN 15879
    WHEN 'ΔΑΦΝΗ ΑΤΤΙΚΗΣ' THEN 15887
    WHEN 'ΘΕΣΣΑΛΟΝΙΚΗ' THEN 1257
    WHEN 'ΚΑΛΛΙΘΕΑ ΑΤΤΙΚΗ' THEN 16022
    WHEN 'ΚΑΛΛΙΘΕΑ ΑΤΤΙΚΗΣ' THEN 16022
    WHEN 'ΚΗΦΙΣΙΑ ΑΤΤΙΚΗΣ' THEN 15931
    WHEN 'ΚΟΡΥΔΑΛΛΟΣ ΑΤΤΙΚΗΣ' THEN 16450
    WHEN 'ΚΟΡΩΠΙ ΑΤΤΙΚΗΣ' THEN 16105
    WHEN 'ΛΥΚΟΒΡΥΣΗ' THEN 15944
    WHEN 'ΜΑΡΟΥΣΙ - ΑΤΤΙΚΗΣ' THEN 15915
    WHEN 'ΜΑΡΟΥΣΙ-ΑΤΤΙΚΗΣ' THEN 15915
    WHEN 'ΜΑΡΟΥΣΙ, ΑΘΗΝΑ' THEN 15915
    WHEN 'ΠΕΙΡΑΙΑΣ ΑΤΤΙΚΗ' THEN 16438
    WHEN 'ΠΕΝΤΕΛΗ - ΑΤΤΙΚΗ' THEN 15969
    WHEN 'ΠΕΡΙΣΤΕΡΙ ΑΤΤΙΚΗΣ' THEN 15989
    WHEN 'ΧΟΛΑΡΓΟΣ' THEN 15956
    WHEN 'ΑΘΗΝΑ - ΑΙΓΑΛΕΩ' THEN 16004
    WHEN 'ΑΘΗΝΑ - ΓΑΛΑΤΣΙ' THEN 15883
    WHEN 'ΑΘΗΝΑ - ΖΩΓΡΑΦΟΥ' THEN 15894
    WHEN 'ΑΘΗΝΑ - ΠΑΓΚΡΑΤΙ' THEN 15875
    WHEN 'ΑΘΗΝΑ - ΠΕΙΡΑΙΑΣ' THEN 16438
    WHEN 'ΑΘΗΝΑ - ΧΑΪΔΑΡΙ' THEN 16016
    WHEN 'ΑΘΗΝΑ - ΧΟΛΑΡΓΟΣ' THEN 15956
    WHEN 'ΑΘΗΝΑ | ΧΑΛΑΝΔΡΙ' THEN 15984
    WHEN 'ΑΘΗΝΑ ΗΛΙΟΥΠΟΛΗ' THEN 15898
    WHEN 'ΑΘΗΝΑ- ΖΩΓΡΑΦΟΥ' THEN 15894
    WHEN 'ΑΘΗΝΑ- ΠΕΙΡΑΙΑΣ' THEN 16438
    WHEN 'ΑΘΗΝΑ- ΠΕΤΡΟΥΠΟΛΗ' THEN 16012
    WHEN 'ΑΘΗΝΑ- ΧΑΪΔΆΡΙ' THEN 16016
    WHEN 'ΑΘΗΝΑ- ΧΑΛΑΝΔΡΙ' THEN 15984
    WHEN 'ΑΘΗΝΑ-ΖΩΓΡΑΦΟΥ' THEN 15894
    WHEN 'ΑΘΗΝΑ-ΚΑΜΑΤΕΡΟ' THEN 16000
    WHEN 'ΑΘΗΝΑ-ΚΗΦΙΣΙΑ' THEN 15931
    WHEN 'ΑΘΗΝΑ-ΝΕΑ ΠΕΝΤΕΛΗ' THEN 15966
    WHEN 'ΑΘΗΝΑ-ΠΑΓΚΡΑΤΙ' THEN 15875
    WHEN 'ΑΘΗΝΑ-ΠΑΠΑΓΟΣ' THEN 15959
    WHEN 'ΑΘΗΝΑ, ΜΑΡΟΥΣΙ' THEN 15915
    WHEN 'ΑΘΗΝΑ, ΜΑΡΟΥΣΙ, ΑΤΤΙΚΗ' THEN 15915
    WHEN 'ΑΘΗΝΑ, ΝΕΑ ΠΕΝΤΕΛΗ' THEN 15966
    WHEN 'ΑΘΗΝΑ, ΝΕΑ ΣΜΥΡΝΗ' THEN 16052
    WHEN 'ΑΘΗΝΑ,ΑΤΤΙΚΗ' THEN 15875
    WHEN 'ΑΘΗΝΑ,ΠΑΓΚΡΑΤΙ' THEN 15875
    WHEN 'ΑΘΗΝΑ,ΧΑΙΔΑΡΙ' THEN 16016
    WHEN 'ΑΙΓΙΝΑ' THEN 16536
    WHEN 'ΑΝΑΒΥΣΣΟΣ ΑΤΤΙΚΗΣ' THEN 16261
    WHEN 'ΑΡΓΥΡΟΥΠΟΛΗ-ΑΤΤΙΚΗ' THEN 16038
    WHEN 'ΑΡΤΑ' THEN 5065
    WHEN 'ΑΤΤΙΚΗ-ΝΕΑ ΕΡΥΘΡΑΙΑ' THEN 15937
    WHEN 'ΑΤΤΙΚΗ-ΝΕΑ ΣΜΥΡΝΗ' THEN 16052
    WHEN 'ΑΤΤΙΚΗ-ΧΑΙΔΑΡΙ' THEN 16016
    WHEN 'ΒΟΥΛΑ' THEN 16070
    WHEN 'ΒΡΙΛΗΣΣΙΑ - ΑΤΤΙΚΗ' THEN 15923
    WHEN 'ΒΥΡΩΝΑΣ' THEN 15879
    WHEN 'ΒΥΡΩΝΑΣ ΑΤΤΙΚΗΣ' THEN 15879
    WHEN 'ΓΕΡΑΚΑΣ ΑΤΤΙΚΗ' THEN 16237
    WHEN 'ΓΕΡΑΚΑΣ, ΑΤΤΙΚΗ' THEN 16237
    WHEN 'ΓΥΘΕΙΟ' THEN 14489
    WHEN 'ΕΛΕΥΣΙΝΑ' THEN 16357
    WHEN 'ΕΛΕΥΣΙΝΑ ΑΤΤΙΚΗΣ' THEN 16357
    WHEN 'ΕΛΛΗΝΙΚΟ' THEN 16469
    WHEN 'ΘΗΒΑ' THEN 8316
    WHEN 'ΙΘΑΚΗ' THEN 10191
    WHEN 'ΚΑΙΣΑΡΙΑΝΗ' THEN 15902
    WHEN 'ΚΑΛΛΙΘΕΑ - ΑΤΤΙΚΗΣ' THEN 16022
    WHEN 'ΚΑΛΛΙΘΕΑ- ΑΤΤΙΚΗ' THEN 16022
    WHEN 'ΚΑΛΛΙΘΕΑ-ΑΤΤΙΚΗ' THEN 16022
    WHEN 'ΚΑΛΛΙΘΕΑ-ΑΤΤΙΚΗΣ' THEN 16022
    WHEN 'ΚΑΜΑΤΕΡΟ ΑΤΤΙΚΗΣ' THEN 16000
    WHEN 'ΚΑΡΔΙΤΣΑ' THEN 6461
    WHEN 'ΚΑΤΕΡΊΝΗ' THEN 2318
    WHEN 'ΚΕΡΚΥΡΑ' THEN 9613
    WHEN 'ΚΗΦΙΣΙΑ - ΑΤΤΙΚΗ' THEN 15931
    WHEN 'ΚΌΡΙΝΘΟΣ' THEN 13957
    WHEN 'ΚΟΡΥΔΑΛΛΟΣ' THEN 16450
    WHEN 'ΛΑΡΙΣΑ' THEN 5974
    WHEN 'ΛΑΥΡΙΟ ΑΤΤΙΚΗΣ' THEN 16113
    WHEN 'ΛΥΚΟΒΡΥΣΗ ΑΤΤΙΚΗΣ' THEN 15944
    WHEN 'ΜΑΓΟΥΛΑ - ΑΤΤΙΚΗ' THEN 16360
    WHEN 'ΜΑΝΔΡΑ ΑΤΤΙΚΗΣ' THEN 16369
    WHEN 'ΜΑΡΟΥΣΙ ΑΘΗΝΑ' THEN 15915
    WHEN 'ΜΑΡΟΥΣΙ, ΑΤΤΙΚΗΣ' THEN 15915
    WHEN 'ΜΕΤΑΜΟΡΦΩΣΗ ΑΤΤΙΚΗΣ' THEN 16689
    WHEN 'ΜΥΤΙΛΗΝΗ' THEN 16719
    WHEN 'ΝΕΑ ΕΡΥΘΡΑΙΑ ΑΤΤΙΚΗΣ' THEN 15937
    WHEN 'ΝΕΑ ΜΑΚΡΗ' THEN 16214
    WHEN 'ΝΕΑ ΠΕΝΤΕΛΗ' THEN 15966
    WHEN 'ΝΕΑ ΣΜΥΡΝΗ, ΑΤΤΙΚΗ' THEN 16052
    WHEN 'ΝΕΟ ΨΥΧΙΚΟ' THEN 15977
    WHEN 'ΝΕΟ ΨΥΧΙΚΟ - ΑΤΤΙΚΗ' THEN 15977
    WHEN 'ΟΙΝΟΦΥΤΑ' THEN 8417
    WHEN 'ΠΑΓΚΡΑΤΙ' THEN 11357
    WHEN 'ΠΑΙΑΝΙΑ' THEN 16229
    WHEN 'ΠΑΙΑΝΙΑ ΑΤΤΙΚΗΣ' THEN 16229
    WHEN 'ΠΑΛΛΗΝΗ' THEN 16243
    WHEN 'ΠΕΙΡΑΙΑΣ - ΑΤΤΙΚΗ' THEN 16438
    WHEN 'ΠΕΙΡΑΙΆΣ -ΑΤΤΙΚΗ' THEN 16438
    WHEN 'ΠΕΙΡΑΙΑΣ,ΑΤΤΙΚΗ' THEN 16438
    WHEN 'ΠΕΝΤΕΛΗ ΑΤΤΙΚΗΣ' THEN 15969
    WHEN 'ΠΙΚΕΡΜΙ' THEN 16252
    WHEN 'ΡΙΤΣΩΝΑ ΕΥΒΟΙΑΣ' THEN 8456
    WHEN 'ΣΙΝΔΟΣ-ΘΕΣΣΑΛΟΝΙΚΗ' THEN 1339
    WHEN 'ΣΠΑΤΑ ΑΤΤΙΚΗΣ' THEN 16277
    WHEN 'ΣΧΗΜΑΤΑΡΙ' THEN 8398
    WHEN 'ΤΑΥΡΟΣ' THEN 16048
    WHEN 'ΤΗΝΟΣ' THEN 18796
    WHEN 'ΤΡΙΠΟΛΗ' THEN 12948
    WHEN 'ΦΥΛΉ, ΑΤΤΙΚΉ' THEN 16432
    WHEN 'ΧΑΙΔΑΡΙ - ΑΤΤΙΚΗ' THEN 16016
    WHEN 'ΧΑΛΑΝΔΡΙ - ΑΤΤΙΚΗ' THEN 15984
    WHEN 'ΧΑΛΑΝΔΡΙ ΑΤΤΙΚΗΣ' THEN 15984
    WHEN 'ΧΑΛΑΝΔΡΙ, ΑΘΗΝΑ' THEN 15984
    WHEN 'ΨΑΧΝΑ ΕΥΒΟΙΑΣ' THEN 8487
    ELSE f_polh_rescom
END
WHERE f_polh_rescom IS NULL;

UPDATE providers
SET f_polh_rescom = CASE TRIM(f_perioxh)

    WHEN 'Π.ΦΆΛΗΡΟ 17561' THEN 16056
    WHEN 'ΑΘΗΝΑ ΒΟΤΑΝΙΚΟΣ' THEN 15875
    WHEN 'ΑΘΗΝΑ - ΜΕΤΣ' THEN 15875
    WHEN 'ΜΕΤΣΟΒΟ ΗΠΕΙΡΟΣ' THEN 4892
    WHEN 'ΛΑΜΙΑ ΦΘΙΩΤΙΔΟΣ' THEN 7691
    WHEN 'ΘΕΣΠΙΕΣ ΒΟΙΩΤΙΑΣ' THEN 8285
    WHEN 'ΘΗΒΑ-ΒΟΙΩΤΙΑΣ' THEN 8316
    WHEN 'ΑΜΦΙΣΣΑ, ΔΗΜΟΣ ΔΕΛΦΩΝ' THEN 9357
    WHEN 'ΚΑΛΛΙΘEΑ  ΑΘHΝΑ' THEN 16022
    WHEN 'ΜΑΡΟΥΣI ΑΤΤΙΚΗΣ' THEN 15915
    WHEN 'ΜΕΣΟΛΟΓΓΙ' THEN 11396
    WHEN 'ΚΙΑΤΟ - ΔΗΜΟΣ ΣΙΚΥΩΝΙΩΝ' THEN 14230
    WHEN 'ΑΧΑΡΝΑΙ - ΑΘΗΝΑ' THEN 16061
    WHEN 'ΚΑΛΑΜΑΤΑ-ΜΕΣΣΗΝΙΑ' THEN 14956

    WHEN 'ΑΤΤΙΚΗ-ΑΘΗΝΑ' THEN 15875
    WHEN 'ΑΘΗΝΑ - ΑΘΗΝΑ' THEN 15875
    WHEN 'ΑΘΗΝΑ-ΔΗΜΟΣ ΑΘΗΝΑΙΩΝ' THEN 15875
    WHEN 'AΘΗΝΑ' THEN 15875
    WHEN 'ΑΘΗΝΑ-ΑΘΗΝΑ' THEN 15875
    WHEN 'AΘΗΝΑ - ΑΤΤΙΚΗΣ' THEN 15875
    WHEN 'AΘΗΝΑ,ΑΤΤΙΚΗ' THEN 15875
    WHEN 'ΑΤΤΙΚΉ -ΑΘΗΝΑ' THEN 15875
    WHEN 'ΑΤΤΙΚΗ ΑΘΗΝΑ' THEN 15875
    WHEN 'ΑΤΤΙΚΗ ΑΘΗΝΑΙΩΝ' THEN 15875
    WHEN 'ΑΤΤΙΚΗ, ΑΘΗΝΑ' THEN 15875
    WHEN 'ΔΗΜΟΣ ΑΘΗΝΑΙΩΝ' THEN 15875

    WHEN 'ΚΑΤΩ ΚΗΦΙΣΙΑ' THEN 15931
    WHEN 'ΑΘΗΝΑ-ΕΡΥΘΡΑΙΑ' THEN 15937
    WHEN 'ΠΑΠΑΓΟΥ' THEN 15959
    WHEN 'ΑΘΗΝΑ- ΠΑΠΑΓΟΥ' THEN 15959

    WHEN 'ΙΛΙΟΝ ΑΤΤΙΚΗΣ' THEN 16008
    WHEN 'ΑΘΗΝΑ - ΙΛΙΟΝ' THEN 16008
    WHEN 'ΙΛΙΟΝ' THEN 16008
    WHEN 'ΙΛΙΟΝ - ΑΤΤΙΚΗ' THEN 16008

    WHEN 'ΠΑΛΑΙΟ ΦΑΛΗΡΟ ΑΤΤΙΚΗΣ' THEN 16056
    WHEN 'ΑΘΗΝΑ-ΠΑΛΑΙΟ ΦΑΛΗΡΟ' THEN 16056
    WHEN 'ΑΘΗΝΑ, ΠΑΛΑΙΟ ΦΑΛΗΡΟ' THEN 16056
    WHEN 'ΑΘΗΝΑ - ΠΑΛΑΙΟ ΦΑΛΗΡΟ' THEN 16056
    WHEN 'ΑΤΤΙΚΗ - ΠΑΛΑΙΟ ΦΑΛΗΡΟ' THEN 16056
    WHEN 'ΠΑΛΑΙΟ ΦΑΛΗΡΟ, ΑΤΤΙΚΗ' THEN 16056

    WHEN 'ΠΑΙΑΝΙΑ - ΠΑΙΑΝΙΑ ΑΤΤΙΚΗΣ' THEN 16229
    WHEN 'Π.ΦΩΚΑΙΑ / ΑΝΑΒΥΣΣΟΣ' THEN 16261

    WHEN 'ΠΕΙΡΑΙΑΣ-ΠΕΙΡΑΙΑΣ' THEN 16438
    WHEN 'ΠΕΙΡΑΙΑΣ - ΠΕΙΡΑΙΑΣ' THEN 16438
    WHEN 'ΠΛΟΙΩΝ ΠΕΙΡΑΙA' THEN 16438

    WHEN 'ΑΓΙΟΣ ΙΩΑΝΝΗΣ ΡΕΝΤΗΣ - ΝΙΚΑΙΑ' THEN 16457
    WHEN 'ΝΙΚΑΙΑ-ΑΓΙΟΣ ΙΩΑΝΝΗΣ ΡΕΝΤΗΣ' THEN 16457

    WHEN 'ΑΘΗΝΑ- Ν. ΚΟΣΜΟΣ' THEN 15875
    WHEN 'ΜΥΤΙΛΗΝΗ - ΛΕΣΒΟΣ' THEN 16719
    WHEN 'ΑΘΗΝΑ - ΜΕΣΟΓΕΙΩΝ' THEN 15875

    WHEN 'ΑΘΉΝΑ - ΜΕΤΑΞΟΥΡΓΕΊΟ' THEN 15875
    WHEN 'ΑΘΗΝΑ- ΜΕΤΑΞΟΥΡΓΕΙΟ' THEN 15875
    WHEN 'ΜΕΤΑΞΟΥΡΓΕΙΟ , ΑΘΗΝΑ' THEN 15875

    WHEN 'ΕΡΜΟΥΠΟΛΗ- ΣΥΡΟΣ' THEN 17632
    WHEN 'ΑΘΗΝΑ, ΟΜΟΝΟΙΑ' THEN 15875

    WHEN 'ΑΘΉΝΑ - ΙΛΊΣΙΑ' THEN 15875
    WHEN 'ΑΘΗΝΑ (ΙΛΙΣΙΑ)' THEN 15875
    WHEN 'ΙΛΙΣΙΑ, ΑΘΗΝΑ' THEN 15875
    WHEN 'ΙΛΙΣΙΑ,ΑΘΗΝΑ ΑΤΤΙΚΗ' THEN 15875

    WHEN 'ΓΚΑΖΙ, ΑΘΗΝΑ' THEN 15875
    WHEN 'ΑΘΗΝΑ - ΜΑΚΡΥΓΙΑΝΝΗ' THEN 15875
    WHEN 'ΧΑΝΙΑ- ΚΟΥΝΟΥΠΙΔΙΑΝΑ' THEN 20483

    ELSE f_polh_rescom
END
WHERE f_polh_rescom IS NULL;

UPDATE providers
SET f_polh_rescom = CASE TRIM(f_perioxh)

    WHEN 'ΑΘΗΝΑ-ΚΑΛΛΙΘΕΑ' THEN 16022
    WHEN 'ΑΘΗΝΑ - ΚΑΛΛΙΘΕΑ' THEN 16022
    WHEN 'ΑΘΗΝΑ ΚΑΛΛΙΘΕΑ' THEN 16022
    WHEN 'ΑΘΗΝΑ,ΚΑΛΛΙΘΕΑ' THEN 16022
    WHEN 'ΚΑΛΛΙΘΈΑ, ΑΘΗΝΑ' THEN 16022

    WHEN 'ΑΘΗΝΑ - ΜΑΓΟΥΛΑ' THEN 16360

    WHEN 'ΑΘΗΝΑ - ΑΡΓΥΡΟΥΠΟΛΗ' THEN 16038
    WHEN 'ΑΘΗΝΑ ΑΡΓΥΡΟΥΠΟΛΗ' THEN 16038
    WHEN 'ΑΘΗΝΑ-ΑΡΓΥΡΟΥΠΟΛΗ' THEN 16038

    WHEN 'ΑΘΗΝΑ - ΝΕΑ ΙΩΝΙΑ' THEN 15952
    WHEN 'ΑΘΗΝΑ-ΝΕΑ ΙΩΝΙΑ' THEN 15952

    WHEN 'ΑΘΗΝΑ - ΠΛΑΚΑ' THEN 15875
    WHEN 'ΑΘΉΝΑ, ΠΛΆΚΑ' THEN 15875

    WHEN 'ΑΘΗΝΑ - ΨΥΧΙΚΟ' THEN 15974
    WHEN 'ΑΘΗΝΑ ,ΨΥΧΙΚΟ' THEN 15974

    WHEN 'ΑΘΗΝΑ ΓΕΡΑΚΑΣ ΑΤΤΙΚΗΣ' THEN 16237
    WHEN 'ΑΘΗΝΑ-ΝΙΚΑΙΑ' THEN 16454
    WHEN 'ΑΘΗΝΑ-ΠΕΡΙΣΤΕΡΙ' THEN 15989
    WHEN 'ΠΕΡΙΣΤΕΡΙ-ΑΘΗΝΑ' THEN 15989
    WHEN 'ΑΘΗΝΑ-ΠΕΥΚΗ' THEN 15941
    WHEN 'ΑΘΗΝΑ, ΚΟΡΥΔΑΛΛΟΣ' THEN 16450
    WHEN 'ΑΤΤΙΚΗ-ΠΕΡΑΜΑ' THEN 16461
    WHEN 'ΓΛΥΦΑΔΑ - ΑΘΗΝΑ' THEN 16034
    WHEN 'ΖΩΓΡΑΦΟΥ - ΑΘΗΝΑ' THEN 15894

    WHEN 'ΑΘΗΝΑ - ΠΑΝΟΡΜΟΥ' THEN 15875

    WHEN 'ΒΟΡΕΙΟΥ ΤΟΜΕΑ ΑΘΗΝΩΝ - ΜΑΡΟΥΣΙ' THEN 15915
    WHEN 'ΜΑΡΟΎΣΙ, ΑΤΤΙΚΉ – ΕΛΛΆΔΑ' THEN 15915

    WHEN 'ΑΘΗΝΑ - ΝΕΑ ΦΙΛΟΘΕΗ' THEN 15980
    WHEN 'ΑΘΗΝΑ, ΔΗΜΟΣ ΧΑΛΑΝΔΡΙΟΥ' THEN 15984

    WHEN 'ΑΘΗΝΑ -ΑΛΚΑΜΕΝΟΥΣ ΚΑΙ ΧΑΡΙΣΣΗΣ' THEN 15875
    WHEN 'ΠΥΡΓΟ ΑΘΗΝΩΝ' THEN 15875
    WHEN 'ΑΘΗΝΑ - ΓΟΥΔΙ' THEN 15875
    WHEN 'ΓΟΥΔΙ ΑΘΗΝΑ' THEN 15875

    WHEN 'ΑΓΙΟΣ ΣΤΕΦΑΝΟΣ-ΑΤΤΙΚΗΣ-14565' THEN 16080

    ELSE f_polh_rescom
END
WHERE f_polh_rescom IS NULL;