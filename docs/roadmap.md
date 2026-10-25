# Roadmap πρώτης έκδοσης

## Τεχνική βάση

- Laravel monolith με Blade και Bootstrap.
- MySQL 8 με `utf8mb4`.
- Docker-based τοπικό περιβάλλον.
- Αγγλικά ονόματα σε κώδικα και βάση, ελληνικό περιβάλλον χρήστη.
- API endpoints μόνο όπου χρειάζονται για εξωτερικά συστήματα ή δυναμικές
  ενέργειες της διεπαφής.

## Φάση 0 — Χαρτογράφηση παλιάς εφαρμογής

- Κατάλογος ενεργών entry points και λειτουργικών ενοτήτων.
- Χάρτης σημερινών πινάκων, πεδίων και σχέσεων.
- Χάρτης CAS, ATLAS, RESCOM, email, PDF και uploads.
- Διάκριση ενεργού, παλιού, δοκιμαστικού και διπλότυπου κώδικα.
- Πίνακας αντιστοίχισης παλιάς λειτουργίας προς νέο module.

## Φάση 1 — Skeleton εφαρμογής

- Laravel και Docker Compose/Sail.
- MySQL, mail catcher και ασφαλές local configuration.
- Bootstrap layout, authentication skeleton και βασικό testing setup.
- CI/linting χωρίς σύνδεση στην production βάση.

## Φάση 2 — Νέος πυρήνας δεδομένων

- Users, roles, permissions και department access.
- Students ως πρόσωπα και internships ως ξεχωριστές συμμετοχές.
- Departments, funding types, organizations, units και contacts.
- Internship snapshots για ιστορικά προσωπικά και στοιχεία φορέα.
- Status history, internal comments και audit log.

## Φάση 3 — Βασική λειτουργική ροή

- Προεγγραφή και μαζική εισαγωγή φοιτητών.
- CAS login και εναλλακτική πρόσβαση φοιτητών χωρίς CAS.
- Καρτέλα πρακτικής, στάδια και χειροκίνητες εγκρίσεις.
- Φόρμες φοιτητή και φορέα.
- Ακύρωση, επανενεργοποίηση και ολοκλήρωση.

## Φάση 4 — Αρχεία και συμφωνητικά

- Παραμετρικές απαιτήσεις εγγράφων ανά χρηματοδότηση.
- Ασφαλές storage εκτός δημόσιων URLs.
- File versioning, review, rejection και resubmission.
- Παραγωγή PDF συμφωνητικού.
- Σταθερή σειρά υπογραφών: φοιτητής, φορέας, ΕΥ.
- Χειροκίνητη εξαίρεση για σκαναρισμένα συμφωνητικά.

## Φάση 5 — Ερωτηματολόγια και email

- Versioned questionnaires και response revisions.
- Κοινά templates με εξαιρέσεις ανά τμήμα όπου απαιτείται.
- Διαχειριζόμενα, versioned email templates.
- Preview και χειροκίνητη αποστολή προκαθορισμένων emails.
- Ιστορικό αποστολών.

## Φάση 6 — Integrations

- CAS adapter.
- ATLAS read-only check με JSON snapshots και προβολή διαφορών.
- RESCOM lookup και χειροκίνητη αποστολή συμβάσεων.
- Ασφαλές TLS, validation, idempotency και request/response history.

## Φάση 7 — Αναφορές και parity

- Αναφορές και exports που χρησιμοποιούνται πραγματικά.
- Στατιστικά ερωτηματολογίων ανά version.
- Οικονομικές και διοικητικές αναφορές.
- Checklist λειτουργικής ισοδυναμίας με την παλιά εφαρμογή.

## Φάση 8 — Migration και cutover

- Επαναλήψιμη μετατροπή παλιού schema στο νέο.
- Μετατροπή serialized δεδομένων σε σχεσιακά δεδομένα ή JSON.
- Μεταφορά αρχείων με checksum και reconciliation.
- Δοκιμαστικές μεταφορές σε staging.
- Τελικός συγχρονισμός, cutover και προσωρινή read-only παλιά εφαρμογή.

## Εκτός πρώτης προτεραιότητας

- Πλήρες UI form builder.
- Αυτόματη ενημέρωση από ATLAS.
- ATLAS write integration.
- Αυτόματη κρυπτογραφική επικύρωση όλων των PDF υπογραφών.
- Πλήρες portal Επιστημονικών Υπευθύνων, πέρα από την προετοιμασία του
  μοντέλου πρόσβασης.
- Αυτόματες ειδοποιήσεις χωρίς ενέργεια χειριστή.
