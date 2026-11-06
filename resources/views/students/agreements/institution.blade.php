@extends('students.agreements.layout')

@section('title', 'Ιδιωτικό Συμφωνητικό Πρακτικής Άσκησης - Ίδρυμα')

@section('content')
<h1>ΙΔΙΩΤΙΚΟ ΣΥΜΦΩΝΗΤΙΚΟ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ<br>(Με κάλυψη ασφαλιστικών εισφορών από τον Ε.Λ.Κ.Ε. σύμφωνα με το άρθρο 69 ν. 4957/2022)</h1>

<p>Στ{{ $department?->polh === 'ΨΑΧΝΑ' ? 'α' : 'ην' }} <x-agreement.value :value="$department?->polh" /> σήμερα .............................., οι κάτωθι συμβαλλόμενοι:</p>

<p><strong>α) Ο Φορέας Υποδοχής</strong> με επωνυμία <x-agreement.value :value="$provider?->f_epwnymia" />, έδρα <x-agreement.value :value="$provider?->headquartersAddress()" />, Α.Φ.Μ. <x-agreement.value :value="$provider?->f_afm" />, Δ.Ο.Υ. <x-agreement.value :value="$provider?->taxOfficeLabel()" /> και νόμιμο εκπρόσωπο τον/την <x-agreement.value :value="$provider?->legalRepresentative()" />.</p>

<p><strong>β) Το Εθνικό και Καποδιστριακό Πανεπιστήμιο Αθηνών</strong>, που εκπροσωπείται νόμιμα από τον/την Ιδρυματικό/ή Υπεύθυνο/η Πρακτικής Άσκησης <x-agreement.value :value="$details['ep_yp_ergou'] ?? null" />.</p>

<p><strong>γ) Ο Ειδικός Λογαριασμός Κονδυλίων Έρευνας του Ε.Κ.Π.Α.</strong>, όπως νομίμως εκπροσωπείται.</p>

<p><strong>δ) Ο/Η Φοιτητής/τρια του Τμήματος</strong> <x-agreement.value :value="$department?->tmhma" /> με τα ακόλουθα στοιχεία:</p>
@include('students.agreements.partials.student-details')

<p>συμφώνησαν και έγιναν αμοιβαίως αποδεκτά τα ακόλουθα:</p>

<p><strong>1.</strong> Ο Φορέας Υποδοχής αποδέχεται τη διεξαγωγή Πρακτικής Άσκησης του/της φοιτητή/τριας στον τόπο εκτέλεσης <x-agreement.value :value="$provider?->internshipAddress()" />, κατόπιν της εγκριτικής απόφασης <x-agreement.value :value="$phaseDetails['apofash_ELKE'] ?? null" />. Η διάρκεια της παρούσας σύμβασης ορίζεται σε <x-agreement.value :value="$student->numMhnes" /> μήνες, με <x-agreement.value :value="$student->wresPerWeek" /> ώρες εβδομαδιαίως. @include('students.agreements.partials.period') Μετά την πάροδο του προαναφερθέντος χρονικού διαστήματος, η σύμβαση λήγει αυτοδικαίως.</p>

<p><strong>2.</strong> Η παρούσα δεν συνιστά σύμβαση εξαρτημένης εργασίας ή οποιασδήποτε άλλης μορφής μισθωτής απασχόλησης. Το αντικείμενο της Πρακτικής Άσκησης καθορίζεται από κοινού από τον επόπτη του Φορέα Υποδοχής, τον επόπτη από την πλευρά του Ε.Κ.Π.Α. και τον/τη φοιτητή/τρια.</p>

<p><strong>3.</strong> Οι φοιτητές/τριες που διεξάγουν Πρακτική Άσκηση υπάγονται υποχρεωτικά στην ασφάλιση του e-Ε.Φ.Κ.Α., σύμφωνα με την παρ. 1 του άρθρου 10 του ν. 2217/1994, μόνο για τον κίνδυνο ατυχήματος. Για την ασφάλιση παροχών ασθενείας σε είδος εφαρμόζεται η παρ. 10 του άρθρου 15 του ν. 3232/2004.</p>

<p><strong>4.</strong> Με βάση την κείμενη νομοθεσία δεν υφίσταται μηνιαία αποζημίωση για την Πρακτική Άσκηση. Το κόστος ασφάλισης βαρύνει τον προϋπολογισμό του έργου <x-agreement.value :value="$details['K_E_ergou'] ?? null" /> που διαχειρίζεται ο Ε.Λ.Κ.Ε. Ε.Κ.Π.Α.</p>

<p><strong>5.</strong> Η υλοποίηση του προγράμματος γίνεται με την ευθύνη όλων των συμβαλλομένων και σύμφωνα με την ισχύουσα νομοθεσία και τον Κανονισμό Πρακτικής Άσκησης. Υπεύθυνος/η Πρακτικής Άσκησης έχει οριστεί ο/η <x-agreement.value :value="trim(($supervisor?->fName ?? '').' '.($supervisor?->lName ?? ''))" />. Επόπτης/τρια από την πλευρά του Φορέα Υποδοχής ορίζεται ο/η <x-agreement.value :value="trim(($provider?->f_epopt_foreaFname ?? '').' '.($provider?->f_epopt_foreaLname ?? ''))" /> (email: <x-agreement.value :value="$provider?->f_epopt_email" />). Ο Φορέας Υποδοχής αναλαμβάνει την υποχρέωση δήλωσης στο Π.Σ. ΕΡΓΑΝΗ.</p>

<p><strong>6.</strong> Αποκλειστικά κατά τόπο αρμόδια δικαστήρια για κάθε διαφορά που τυχόν προκύψει από την παρούσα σύμβαση ορίζονται τα δικαστήρια της Αθήνας.</p>

<table class="signatures">
    <tr><th colspan="2">ΟΙ ΣΥΜΒΑΛΛΟΜΕΝΟΙ</th></tr>
    <tr><th>ΓΙΑ ΤΟΝ ΦΟΡΕΑ ΥΠΟΔΟΧΗΣ</th><th>ΓΙΑ ΤΟ Ε.Κ.Π.Α.<br>Ο/Η Ιδρυματικός/ή Υπεύθυνος/η</th></tr>
    <tr class="signature-space"><td></td><td></td></tr>
    <tr><td><x-agreement.value :value="trim(($provider?->f_ekprFname ?? '').' '.($provider?->f_ekprLname ?? ''))" /></td><td><x-agreement.value :value="$details['ep_yp_ergou'] ?? null" /></td></tr>
</table>
<table class="signatures">
    <tr><th>Ο/Η ΦΟΙΤΗΤΗΣ/ΤΡΙΑ</th><th>ΓΙΑ ΤΟΝ Ε.Λ.Κ.Ε. Ε.Κ.Π.Α.</th></tr>
    <tr class="signature-space"><td></td><td></td></tr>
    <tr><td><x-agreement.value :value="trim(($student->fName ?? '').' '.($student->lName ?? ''))" /></td><td><x-agreement.value :value="$details['legal_representative'] ?? null" /><br><x-agreement.value :value="$details['legal_representative_title'] ?? null" /></td></tr>
</table>
@endsection
