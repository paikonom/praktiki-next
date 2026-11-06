@extends('students.agreements.layout')

@section('title', 'Ιδιωτικό Συμφωνητικό Πρακτικής Άσκησης - Φορέας Υποδοχής')

@section('content')
<h1>ΙΔΙΩΤΙΚΟ ΣΥΜΦΩΝΗΤΙΚΟ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ ΦΟΙΤΗΤΩΝ Ε.Κ.Π.Α.<br>(με Χρηματοδότηση από τον Φορέα Υποδοχής)</h1>

<p>Στ{{ $department?->polh === 'ΨΑΧΝΑ' ? 'α' : 'ην' }} <x-agreement.value :value="$department?->polh" /> σήμερα .............................., μεταξύ:</p>

<ol>
    <li>Του Εθνικού και Καποδιστριακού Πανεπιστημίου Αθηνών, που εδρεύει στην Αθήνα και εκπροσωπείται νόμιμα από τον/την Πρόεδρο του Τμήματος <x-agreement.value :value="$department?->proedros" />, <x-agreement.value :value="$department?->proedros_vathm" />.</li>
    <li>Του Φορέα Υποδοχής με επωνυμία <x-agreement.value :value="$provider?->f_epwnymia" />, Α.Φ.Μ. <x-agreement.value :value="$provider?->f_afm" />, έδρα <x-agreement.value :value="$provider?->headquartersAddress()" /> και νόμιμο εκπρόσωπο τον/την <x-agreement.value :value="$provider?->legalRepresentative()" />.</li>
    <li>Του/της φοιτητή/τριας με τα ακόλουθα στοιχεία:</li>
</ol>

@include('students.agreements.partials.student-details')

<p>συμφώνησαν και έγιναν αμοιβαίως αποδεκτά τα ακόλουθα:</p>

<ol>
    <li>Η Συνέλευση του Τμήματος με την απόφαση <x-agreement.value :value="$phaseDetails['apofash_tmhmatos'] ?? null" /> ορίζει ως Επόπτη Πρακτικής Άσκησης τον/την <x-agreement.value :value="trim(($supervisor?->fName ?? '').' '.($supervisor?->lName ?? ''))" />.</li>
    <li>Ο Φορέας Υποδοχής αναλαμβάνει να απασχολήσει τον/την ασκούμενο/η στον τόπο εκτέλεσης <x-agreement.value :value="$provider?->internshipAddress()" /> για διάστημα <x-agreement.value :value="$student->numMhnes" /> μηνών, με ωράριο <x-agreement.value :value="$student->wresPerDays" /> ωρών ημερησίως. @include('students.agreements.partials.period')</li>
    <li>Ο Φορέας Υποδοχής ορίζει ως επόπτη τον/την <x-agreement.value :value="trim(($provider?->f_epopt_foreaFname ?? '').' '.($provider?->f_epopt_foreaLname ?? ''))" />, στη θέση <x-agreement.value :value="$provider?->f_epopt_foreaThesh" />, ο/η οποίος/α διαθέτει την απαιτούμενη κατάρτιση και εμπειρία: <x-agreement.value :value="$provider?->f_epopt_foreaKatartish" />.</li>
    <li>Το αντικείμενο της Πρακτικής Άσκησης εστιάζεται στο εξής: <x-agreement.value :value="$provider?->f_antikeim_Praktikhs" />, με αναμενόμενα αποτελέσματα: <x-agreement.value :value="$provider?->f_apotel_Praktikhs" />.</li>
    <li>Η απασχόληση αποτελεί Πρακτική Άσκηση στο πλαίσιο του Προγράμματος Σπουδών του Τμήματος <x-agreement.value :value="$department?->tmhma" />.</li>
    <li>Η αμοιβή καλύπτεται εξ ολοκλήρου από τον Φορέα Υποδοχής και ορίζεται συνολικά σε <x-agreement.value :value="$student->payTotal" /> € και μηνιαίως σε <x-agreement.value :value="$student->payPerMonth" /> €, συμπεριλαμβανομένων των νόμιμων κρατήσεων και εισφορών.</li>
    <li>Οι φοιτητές/τριες υπάγονται υποχρεωτικά στην ασφάλιση του e-Ε.Φ.Κ.Α. μόνο για τον κίνδυνο ατυχήματος, σύμφωνα με την ισχύουσα νομοθεσία.</li>
    <li>Με τη λήξη της Πρακτικής Άσκησης ο Φορέας Υποδοχής συντάσσει την Έκθεση Επίδοσης και τη Βεβαίωση Εκτέλεσης, ενώ ο/η φοιτητής/τρια συντάσσει την Έκθεση Πεπραγμένων.</li>
    <li>Ο/Η Επόπτης/τρια Πρακτικής Άσκησης συντάσσει τη συνολική έκθεση πεπραγμένων και τη βαθμολογία με τις αντίστοιχες πιστωτικές μονάδες.</li>
    <li>Ο/Η φοιτητής/τρια ακολουθεί το ωράριο και τους κανονισμούς του Φορέα, διατηρεί εμπιστευτικές τις πληροφορίες που λαμβάνει και συναινεί στην αναγκαία επεξεργασία προσωπικών δεδομένων.</li>
    <li>Τα εμπλεκόμενα μέρη τηρούν τον Γενικό Κανονισμό Προστασίας Προσωπικών Δεδομένων και τις σχετικές διατάξεις.</li>
    <li>Το συμφωνητικό δύναται να λυθεί μονομερώς από οποιοδήποτε από τα τρία μέρη για σημαντικό λόγο, μετά από τεκμηριωμένη αιτιολόγηση και κοινοποίηση στα άλλα μέρη, όσο το συμφωνητικό είναι ενεργό.</li>
</ol>

<p>Στο παρόν προσαρτάται ο Κανονισμός Πρακτικής Άσκησης του Τμήματος <x-agreement.value :value="$department?->tmhma" />, ο οποίος αποτελεί αναπόσπαστο μέρος αυτού.</p>
<p>Αφού οι συμβαλλόμενοι συνομολόγησαν και συναποδέχθηκαν τα ανωτέρω, συντάχθηκε το παρόν σε τρία αντίτυπα.</p>

<table class="signatures">
    <tr><th colspan="3">ΟΙ ΣΥΜΒΑΛΛΟΜΕΝΟΙ</th></tr>
    <tr><th>ΓΙΑ ΤΟ Ε.Κ.Π.Α.</th><th>ΓΙΑ ΤΟΝ ΦΟΡΕΑ ΥΠΟΔΟΧΗΣ</th><th>Ο/Η ΑΣΚΟΥΜΕΝΟΣ/Η ΦΟΙΤΗΤΗΣ/ΤΡΙΑ</th></tr>
    <tr class="signature-space"><td></td><td></td><td></td></tr>
    <tr><td><x-agreement.value :value="$department?->proedros" /><br><x-agreement.value :value="$department?->proedros_vathm" /></td><td><x-agreement.value :value="trim(($provider?->f_ekprFname ?? '').' '.($provider?->f_ekprLname ?? ''))" /></td><td><x-agreement.value :value="trim(($student->fName ?? '').' '.($student->lName ?? ''))" /></td></tr>
</table>
@endsection
