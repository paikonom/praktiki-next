# Απλός χάρτης της εφαρμογής

## Όταν ανοίγει μία σελίδα

Κάθε αίτημα προς την εφαρμογή περνά από:

```text
Browser
→ public/index.php
→ bootstrap/app.php
→ routes/web.php
→ κατάλληλος controller ή route
→ HTML ή JSON απάντηση
```

Το `public/index.php` και οι βασικές μέθοδοι όπως `handleRequest()` παρέχονται από το Laravel.

## `bootstrap/app.php`

- `withRouting`: δηλώνει ότι οι διευθύνσεις των σελίδων μας βρίσκονται στο `routes/web.php`.
- `withMiddleware`: στέλνει έναν μη συνδεδεμένο επισκέπτη στο σωστό login.
- `withExceptions`: επιστρέφει JSON στα AJAX/API σφάλματα.
- `create`: επιστρέφει την έτοιμη εφαρμογή Laravel.

## Τύποι σύνδεσης

```text
web      = χειριστής ή admin
student  = φοιτητής
provider = φορέας για συγκεκριμένη πρακτική φοιτητή
scientific_supervisor     = Επιστημονικός Υπεύθυνος
```

Ο φορέας και ο φοιτητής χρησιμοποιούν το `Student` model για τη σύνδεση. Ο φορέας συνδέεται για την πρακτική ενός συγκεκριμένου φοιτητή.

## Views

Η τελεία σε ένα όνομα view σημαίνει υποφάκελο:

```text
view('home')          → resources/views/home.blade.php
view('students.show') → resources/views/students/show.blade.php
```

## Τι είναι δικό μας

- Οι περισσότερες ρυθμίσεις μέσα στα callbacks του `bootstrap/app.php`.
- Τα routes στο `routes/web.php`.
- Οι controllers, τα models, τα views και οι κανόνες της πρακτικής.

Δεν χρειάζεται να μάθουμε την εσωτερική υλοποίηση έτοιμων λειτουργιών του Laravel, όπως `auth()`, `handleRequest()` και `create()`.
