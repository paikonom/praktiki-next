# Σχέδιο μεταφοράς παλιάς βάσης

## Στόχος

Η παλιά βάση μεταφέρεται στη νέα εφαρμογή χωρίς να αλλάξει κατά την ανάλυση ή
τις δοκιμές. Η μεταφορά θα είναι επαναλήψιμη, θα διατηρεί αντιστοίχιση παλιών
και νέων IDs και θα συνοδεύεται από ελέγχους συμφωνίας πριν από το cutover.

Το παρόν έγγραφο είναι αρχικό migration map. Δεν αποτελεί ακόμη τελικό schema
ούτε άδεια εκτέλεσης εισαγωγής σε production.

## Κανόνες ασφαλείας

- Η production βάση παραμένει read-only κατά το inventory και τις δοκιμές.
- Δεν εμφανίζονται ή καταγράφονται passwords, προσωπικά στοιχεία ή πλήρη
  serialized payloads σε logs και αναφορές.
- Τα παλιά primary keys δεν γίνονται primary keys της νέας βάσης. Αποθηκεύονται
  ως `legacy_id` ή σε πίνακα αντιστοίχισης.
- Κάθε importer υποστηρίζει dry run, επανεκτέλεση και bounded batches.
- Προβληματικές εγγραφές μπαίνουν σε quarantine report και δεν διορθώνονται
  σιωπηρά.
- Μετά από κάθε δοκιμή συγκρίνονται πλήθη, συσχετίσεις και checksums.

## Τι γνωρίζουμε για την παλιά βάση

Η βάση περιέχει 29 πίνακες στο τοπικό dump. Οι περισσότερες σχέσεις δεν προστατεύονται
με foreign keys. Το `foreas.sid -> students.sid` είναι η μόνη ρητή σχέση που
εντοπίστηκε στο dump. Οι υπόλοιπες πρέπει να επιβεβαιωθούν από τα δεδομένα και
τον ενεργό κώδικα.

### Χειριστές και πρόσβαση

Ο παλιός `users` περιέχει `uid`, `username`, password hash, στοιχεία
επικοινωνίας, `role`, `viewOnly` και `tmhmataAccess`.

Η ενεργή εφαρμογή:

- αναζητά τον χειριστή με `users.username`,
- επαληθεύει το password με `password_verify()`,
- αποθηκεύει στο session τα `role`, `viewOnly` και `tmhmataAccess`,
- χρησιμοποιεί το `tmhmataAccess` ως λίστα `epyp.atlasId`.

Υπάρχει και πίνακας `users-tmhmata` με `user_id`, `tmhma_id`,
`tmhma_Atlas_id` και `funding_access`, αλλά δεν εντοπίστηκε χρήση του στον
ενεργό PHP κώδικα. Δεν θεωρείται authoritative πριν συγκριθεί με το
`users.tmhmataAccess`.

Προτεινόμενη μεταφορά:

```text
legacy users                  -> users
legacy users.role             -> roles / user role assignment
legacy users.viewOnly         -> permission mapping
legacy users.tmhmataAccess    -> user_department_access
legacy users-tmhmata          -> comparison source, then access/funding mapping
```

Οι υπάρχοντες password hashes θα ελεγχθούν μόνο ως προς τον αλγόριθμο και το
format. Αν είναι συμβατοί με το Laravel, μεταφέρονται αυτούσιοι και γίνεται
rehash μετά από επιτυχημένο login όταν χρειάζεται. Αν δεν είναι συμβατοί,
ακολουθεί ελεγχόμενο password reset. Δεν δημιουργούμε χειροκίνητα νέους
λογαριασμούς πριν ολοκληρωθεί αυτός ο έλεγχος.

### Τμήματα και Επιστημονικοί Υπεύθυνοι

Ο legacy πίνακας `epyp` συνδυάζει στοιχεία τμήματος, στοιχεία Επιστημονικού Υπευθύνου και
συνδέσεις προς διαφορετικούς χειριστές ανά χρηματοδότηση (`uid`,
`uid_Institution`, `uid_InternshipProvider`). Το `atlasId` χρησιμοποιείται ως
επιχειρησιακό identifier του τμήματος και συνδέεται με `students.tmhma` και
`users.tmhmataAccess`.

Προτεινόμενος διαχωρισμός:

```text
legacy epyp.atlasId           -> departments.atlas_id
legacy epyp department data   -> departments
legacy epyp person data       -> department_contacts / scientific_supervisors
legacy epyp uid fields        -> department user/funding access
```

Το legacy `epyp2` δεν χρησιμοποιείται στον ενεργό PHP κώδικα και αντιμετωπίζεται ως
πιθανός παλιός ή βοηθητικός πίνακας μέχρι να ελεγχθεί το περιεχόμενό του.

### Φοιτητές και πρακτικές

Ο παλιός `students` περιέχει μαζί:

- ταυτότητα και προσωπικά στοιχεία φοιτητή,
- στοιχεία μίας συμμετοχής σε πρακτική,
- οικονομικά στοιχεία,
- αναγνωριστικά ATLAS και RESCOM,
- τρέχουσα φάση και χρηματοδότηση,
- counters και παλιές ενδείξεις κατάστασης.

Επειδή ένας φοιτητής μπορεί να έχει περισσότερες από μία πρακτικές, δεν γίνεται
αντιγραφή μιας γραμμής `students` σε έναν νέο `user`.

Προτεινόμενος διαχωρισμός:

```text
legacy students identity fields       -> students / persons
legacy students participation fields  -> internships
legacy students personal snapshot     -> internship_student_snapshots
legacy students.sid                    -> internships.legacy_id
legacy students.uuid                   -> internships.legacy_uuid
legacy students.tmhma                  -> departments μέσω epyp.atlasId
legacy students funding                -> funding_types
legacy ATLAS/RESCOM payloads            -> integration snapshots
```

Η ταυτοποίηση ίδιου φυσικού προσώπου μεταξύ πολλών εγγραφών δεν θα βασιστεί σε
ένα μόνο πεδίο χωρίς reconciliation. Θα εξεταστούν Αριθμός Μητρώου, CAS data
και άλλα διαθέσιμα identifiers, με ξεχωριστή λίστα αμφίβολων αντιστοιχίσεων.

Ο `students_2022_2023` δεν χρησιμοποιείται στον ενεργό κώδικα και θεωρείται
archive/snapshot. Θα συγκριθεί με τον `students`, αλλά δεν θα εισαχθεί δεύτερη
φορά χωρίς αποδεδειγμένα μοναδικές εγγραφές.

### Φορείς και στοιχεία πρακτικής

Ο `foreas` συνδέεται ρητά με `students.sid` και περιέχει στοιχεία φορέα,
εκπροσώπου, επόπτη και αντικειμένου πρακτικής. Τα στοιχεία αυτά πρέπει να
διατηρηθούν ιστορικά ακόμη και αν ο φορέας αλλάξει αργότερα.

Προτεινόμενη μεταφορά:

```text
legacy foreas legal identity      -> organizations
legacy foreas unit/contact data   -> organization_units / contacts
legacy foreas row                 -> internship_organization_snapshot
```

Η ομαδοποίηση με ΑΦΜ δεν θα εφαρμοστεί πριν απαντηθεί η ανοιχτή ερώτηση για
διαφορετικές μονάδες ή επωνυμίες με το ίδιο ΑΦΜ.

Ο `foreas_backup_before_entities_20260918` είναι backup table και δεν αποτελεί
κανονική πηγή migration. Χρησιμοποιείται μόνο για reconciliation συγκεκριμένων
διορθώσεων.

### Καταστάσεις και ροή

Ο `states_student` έχει μία γραμμή ανά `sid`, αλλά αποθηκεύει κάθε κατάσταση
σε διαφορετική στήλη. Αυτό δεν μεταφέρεται ως νέος wide table.

Προτεινόμενος μετασχηματισμός:

```text
legacy states_student workflow columns -> internship status/current state
legacy state changes                    -> status history where reconstructable
legacy document columns                 -> internship document requirements/status
legacy payed/block/cancel               -> explicit internship flags or events
```

Οι counters και οι `OLD_*` στήλες του `students` θα χρησιμοποιηθούν μόνο για
reconciliation, εκτός αν αποδειχθεί ότι αποτελούν ενεργή επιχειρησιακή
κατάσταση.

### Έγγραφα και φυσικά αρχεία

Ο `filesTable` περιγράφει τύπους εγγράφων, σειρά, οδηγίες, υπογραφές,
χρηματοδότηση και δικαιώματα. Ο `files` περιέχει παλιές ειδικές ενδείξεις ανά
`sid`. Η κύρια κατάσταση πολλών εγγράφων βρίσκεται στον `states_student`.

Προτεινόμενη μεταφορά:

```text
legacy filesTable              -> document_types / document requirement rules
legacy states_student columns  -> internship_documents
legacy physical PDFs           -> document_versions
legacy document code           -> document_types.code
```

Η φυσική μεταφορά ακολουθεί το ξεχωριστό `file-migration-plan.md` με manifest,
SHA-256 και reconciliation.

### Ερωτηματολόγια και απογραφικά

Οι `questStudent`, `questForeas` και `questYpeuth` αποθηκεύουν serialized
answers ανά `sid`. Ο `erwthmalogiaQuests` περιέχει ερωτήσεις και version. Οι
`apografikaIn`, `apografikaOut` και `outputAnswers` περιέχουν επιπλέον
serialized δεδομένα και responses.

Προτεινόμενη μεταφορά:

```text
legacy erwthmalogiaQuests -> questionnaire templates/questions/versions
legacy quest*             -> questionnaire responses and initial revision
legacy apografika*        -> integration submissions and immutable snapshots
legacy outputAnswers      -> output submission snapshot, after semantics check
```

Κάθε serialized payload αποκωδικοποιείται με ελεγχόμενο parser. Αποτυχίες
πηγαίνουν σε quarantine και δεν χάνονται ούτε μετατρέπονται σιωπηρά.

### Φάσεις, χρηματοδότηση και οικονομικά

Οι `faseis`, `faseis_Institution` και `faseis_IntershipProv` έχουν παρόμοια
δομή ανά τύπο χρηματοδότησης. Οι `global_stoixeia` και `analysh_kostous`
κρατούν παραμέτρους έργου και οικονομικά στοιχεία.

Προτεινόμενη μεταφορά:

```text
legacy faseis*            -> application periods/phases με funding_type
legacy global_stoixeia    -> funding/project configuration
legacy analysh_kostous    -> cost configuration or historical cost snapshot
```

Οι τρεις πίνακες φάσεων θα συγχωνευθούν μόνο αφού συγκριθούν οι μικρές
διαφορές πεδίων και οι ενεργές διαδρομές του κώδικα.

### Παραμετρικά πεδία και RESCOM

Οι `cols`, `cols2` και `cols_input` περιγράφουν δυναμικά πεδία/φόρμες. Οι
`espaQuestionsOld` και `espaQuestions_new` περιγράφουν εκδόσεις ερωτήσεων ή
microdata για εξωτερική χρήση.

Δεν αντιγράφονται μηχανικά στο νέο schema. Πρώτα καταγράφεται ποια entries
χρησιμοποιούνται από τις ενεργές φόρμες και ποια είναι παλιές εκδόσεις.

### Πίνακες που χρειάζονται ειδική απόφαση

| Legacy table | Αρχική αντιμετώπιση |
| --- | --- |
| `loginData` | Έλεγχος semantics και ευαίσθητων δεδομένων πριν αποφασιστεί retention |
| `epyp2` | Πιθανός παλιός/βοηθητικός πίνακας, δεν χρησιμοποιείται ενεργά |
| `students_2022_2023` | Archive, reconciliation μόνο μέχρι να βρεθούν μοναδικές εγγραφές |
| `foreas_backup_before_entities_20260918` | Backup table, όχι κανονική πηγή import |
| `questYpeuth` | Δεν βρέθηκε ενεργή χρήση· έλεγχος δεδομένων πριν χαρακτηριστεί archive |
| `outputAnswers` | Δεν βρέθηκε ενεργή χρήση· απαιτείται επιβεβαίωση semantics |

## Προτεινόμενη σειρά migration

1. Departments, funding types και reference data.
2. Χειριστές, ρόλοι και πρόσβαση ανά τμήμα/χρηματοδότηση.
3. Μοναδικά πρόσωπα/φοιτητές και CAS identifiers.
4. Πρακτικές με μόνιμο legacy SID mapping.
5. Φορείς, μονάδες, contacts και snapshots πρακτικής.
6. Καταστάσεις, ιστορικό και οικονομικά snapshots.
7. Document types, document records και PDF versions.
8. Questionnaire templates, responses και revisions.
9. ATLAS/RESCOM submissions, snapshots και ιστορικό εξωτερικών ενεργειών.
10. Email history και audit log, όπου τα παλιά δεδομένα το επιτρέπουν.

## Έλεγχοι πριν οριστικοποιηθεί το νέο schema

Χρειάζονται read-only aggregates, χωρίς εξαγωγή προσωπικών δεδομένων:

- πλήθος γραμμών ανά πίνακα,
- duplicate και null usernames,
- τιμές και πλήθη ανά `users.role` και `viewOnly`,
- format/prefix των password hashes χωρίς εμφάνιση hashes,
- σύγκριση `users.tmhmataAccess` με `users-tmhmata`,
- orphan `users-tmhmata.user_id`,
- μοναδικότητα και πληρότητα `epyp.atlasId`,
- orphan `students.tmhma` έναντι `epyp.atlasId`,
- πολλαπλές εγγραφές πρακτικής ανά Αριθμό Μητρώου/CAS identifier,
- orphan ή πολλαπλές γραμμές ανά `sid` σε `foreas`, `states_student` και
  questionnaires,
- πλήθος και εγκυρότητα serialized payloads,
- συμφωνία document states, `filesTable` και φυσικών PDF.

## Επόμενο παραδοτέο

Μετά τα aggregates θα προκύψει ο τελικός πίνακας mapping ανά legacy column:

```text
legacy table.column -> new table.column -> transform -> validation
```

Μόνο τότε θα δημιουργηθούν τα Laravel migrations και οι import commands.
