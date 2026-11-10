@extends('students.agreements.institution_layout')


@section('title', 'Ιδιωτικό Συμφωνητικό Πρακτικής Άσκησης - Ίδρυμα')
@section('content')
<h1>ΙΔΙΩΤΙΚΟ ΣΥΜΦΩΝΗΤΙΚΟ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ<br>(Με κάλυψη ασφαλιστικών εισφορών από τον Ε.Λ.Κ.Ε. σύμφωνα με το άρθρο 69 ν. 4957/2022)</h1>

<p>Στ{{ $department?->polh === 'ΨΑΧΝΑ' ? 'α' : 'ην' }} <x-pdf.value :value="$department?->polh" /> σήμερα .............................., οι κάτωθι συμβαλλόμενοι:</p>

<p><strong>α)</strong> Το νομικό πρόσωπο με την επωνυμία  με επωνυμία <x-pdf.value :value="$provider?->f_epwnymia" />, 
με έδρα <x-pdf.value :value="$provider?->headquartersAddress()" />,
με  Α.Φ.Μ. <x-pdf.value :value="$provider?->f_afm" />, 
Δ.Ο.Υ. <x-pdf.value :value="$provider?->taxOfficeLabel()" /> 
και εκπροσωπείται νόμιμα τον/την <x-pdf.value :value="$provider?->legalRepresentative()" />
.</p>

<p><strong>β)</strong> Το Εθνικό και Καποδιστριακό Πανεπιστήμιο Αθηνών 
που εκπροσωπείται νόμιμα από τον/την Ιδρυματικό/ή 
Υπεύθυνο/η Πρακτικής Άσκησης
 <x-pdf.value :value="$praktiki?->details['ep_yp_ergou'] ?? null" /> ,
 <x-pdf.value :value="$praktiki?->details['ep_yp_ergou_title'] ?? null" />
 .</p>

<p><strong>γ</strong>) Ο Ειδικός Λογαριασμός Κονδυλίων Έρευνας του ου Εθνικού και Καποδιστριακού Πανεπιστημίου Αθηνών,
που εκπροσωπείται νόμιμα από 
<x-pdf.value :value="$praktiki?->details['legal_representative'] ?? null" /> ,
 <x-pdf.value :value="$praktiki?->details['legal_representative_title'] ?? null" />
, στο εξής ΕΛΚΕ ΕΚΠΑ. .</p>

<p><strong>δ)</strong> Ο/Η Φοιτητής/τρια του Τμήματος <x-pdf.value :value="$department?->tmhma" /> με τα ακόλουθα στοιχεία:</p>
<table class="details">
    <tr><td>Ονοματεπώνυμο</td><td><x-pdf.value :value="trim(($student->fName ?? '').' '.($student->lName ?? ''))" /></td></tr>
    <tr><td>Ονοματεπώνυμο πατέρα</td><td><x-pdf.value :value="trim(($student->fatFName ?? '').' '.($student->fatLName ?? ''))" /></td></tr>
    <tr><td>Ονοματεπώνυμο μητέρας</td><td><x-pdf.value :value="trim(($student->momFName ?? '').' '.($student->momLName ?? ''))" /></td></tr>
    <tr><td>Ημερομηνία γέννησης</td><td><x-pdf.value :value="$student->dob ? \Illuminate\Support\Carbon::parse($student->dob)->format('d/m/Y') : null" /></td></tr>
    <tr><td>Α.Φ.Μ.</td><td><x-pdf.value :value="$student->afm" /></td></tr>
    <tr><td>Α.Μ.Κ.Α.</td><td><x-pdf.value :value="$student->amka" /></td></tr>
    <tr><td>Α.Δ.Τ.</td><td><x-pdf.value :value="$student->adt" /></td></tr>
    <tr><td>Διεύθυνση κατοικίας</td>
    <td>
        <x-pdf.value :value="$student->studentAddress()" />
    </td></tr>
    <tr><td>Τηλέφωνο / email</td><td><x-pdf.value :value="trim(($student->tel ?? '').' / '.($student->email ?? ''), ' /')" /></td></tr>
    <tr><td>Τμήμα</td><td><x-pdf.value :value="$department?->tmhma" /></td></tr>
    <tr><td>Αριθμός Μητρώου Φοιτητή</td><td><x-pdf.value :value="$student->AMF" /></td></tr>
</table>


<p>συμφώνησαν και έγιναν αμοιβαίως αποδεκτά τα ακόλουθα:</p>

<p><strong>1.</strong> Ο Φορέας Υποδοχής αποδέχεται τη διεξαγωγή Πρακτικής Άσκησης του/της φοιτητή/τριας στον τόπο εκτέλεσης <x-pdf.value :value="$provider?->internshipAddress()" />, κατόπιν της εγκριτικής απόφασης <x-pdf.value :value="$phase?->details['apofash_ELKE'] ?? null" />. Η διάρκεια της παρούσας σύμβασης ορίζεται σε <x-pdf.value :value="$student->numMhnes" /> μήνες, με <x-pdf.value :value="$student->wresPerWeek" /> ώρες εβδομαδιαίως. @include('students.agreements.partials.period') Μετά την πάροδο του προαναφερθέντος χρονικού διαστήματος, η σύμβαση λήγει αυτοδικαίως.</p>

<p><strong>2.</strong> Η παρούσα δεν συνιστά σε καμία περίπτωση σύμβαση εξηρτημένης εργασίας ή οποιασδήποτε άλλης μορφής μισθωτής
απασχόλησης της/του φοιτήτριας/τή στον Φορέα Υποδοχής, καθώς δεν αποσκοπεί στην παροχή εργασίας ή παραγωγικού
αποτελέσματος οποιουδήποτε είδους. Η/Ο φοιτήτρια/τής θα εκτελεί συγκεκριμένες εργασίες στους χώρους εγκατάστασης του Φορέα
Υποδοχής στο πλαίσιο δραστηριότητας του, η οποία συνδέεται με αντικείμενα που περιλαμβάνονται στις γνωστικές περιοχές του
προγράμματος σπουδών όπου φοιτά και είναι συναφή με το σκοπό της Πρακτικής Άσκησης. Το αντικείμενο της Πρακτικής Άσκησης
της/ου φοιτήτριας/τή καθορίζεται από κοινού από τον επόπτη Φορέα Υποδοχής, τον επόπτη Πρακτικής Άσκησης από την πλευρά του
ΕΚΠΑ και από την/τον φοιτητή/φοιτήτρια. H Πρακτική Άσκηση πραγματοποιείται σύμφωνα με την εκάστοτε ισχύουσα νομοθεσία.</p>

<p><strong>3.</strong> Οι φοιτητές/τριες που διεξάγουν Πρακτική Άσκηση υπάγονται υποχρεωτικά στην ασφάλιση του e-Ε.Φ.Κ.Α., σύμφωνα με την παρ. 1 του άρθρου 10 του ν. 2217/1994, μόνο για τον κίνδυνο ατυχήματος. Για την ασφάλιση παροχών ασθενείας σε είδος εφαρμόζεται η παρ. 10 του άρθρου 15 του ν. 3232/2004.</p>

<p><strong>4.</strong> Με βάση την κείμενη νομοθεσία δεν υφίσταται μηνιαία αποζημίωση για την Πρακτική Άσκηση. Το κόστος ασφάλισης βαρύνει τον προϋπολογισμό του έργου <x-pdf.value :value="$praktiki?->details['K_E_ergou'] ?? null" /> που διαχειρίζεται ο Ε.Λ.Κ.Ε. Ε.Κ.Π.Α.</p>

<p><strong>5.</strong>
    Η υλοποίηση του προγράμματος θα γίνει με την ευθύνη όλων των συμβαλλομένων
    που αναλαμβάνουν ρητά την πιστή τήρηση των υποχρεώσεων και ρυθμίσεων
    της σχετικής νομοθεσίας για την διεξαγωγή της Πρακτικής Άσκησης,
    του Κανονισμού Πρακτικής Άσκησης ΕΚΠΑ
    (ή/και του εσωτερικού κανονισμού Πρακτικής Άσκησης του Π.Π.Σ. ή του Π.Μ.Σ.)
    και σύμφωνα με τους κανόνες που επιβάλλει η δεοντολογία και η καλή πίστη
    για την εκπλήρωση των στόχων της Πρακτικής Άσκησης.

    Η/Ο φοιτήτρια/τής υποχρεούται να ακολουθεί το ωράριο λειτουργίας
    του Φορέα Υποδοχής, τους κανονισμούς εργασίας, τους ισχύοντες κανόνες
    ασφάλειας και υγιεινής, καθώς και κάθε άλλη ρύθμιση ή κανονισμό
    που ισχύει για το προσωπικό του Φορέα Υποδοχής.

    Υπεύθυνος Πρακτικής Άσκησης έχει οριστεί ο/η κ.
    <x-pdf.value :value="trim(($supervisor?->fName ?? '').' '.($supervisor?->lName ?? ''))" />.

    Επόπτης Πρακτικής Άσκησης από την πλευρά του Φορέα Υποδοχής
    ορίζεται ο/η κ.
    <x-pdf.value :value="trim(($provider?->f_epopt_foreaFname ?? '').' '.($provider?->f_epopt_foreaLname ?? ''))" />
    (email: <x-pdf.value :value="$provider?->f_epopt_email" />).

    Ο Φορέας Υποδοχής αναλαμβάνει την υποχρέωση να δηλώσει
    την/τον φοιτήτρια/τή στο Πληροφοριακό Σύστημα ΕΡΓΑΝΗ
    και να αποστείλει τα απαραίτητα αποδεικτικά
    στο Γραφείο Πρακτικής Άσκησης ΕΚΠΑ.</p>

<p><strong>6.</strong> Αποκλειστικά κατά τόπο αρμόδια δικαστήρια για κάθε διαφορά που τυχόν προκύψει από την παρούσα σύμβαση ορίζονται τα δικαστήρια της Αθήνας.</p>


<table class="signatures" style="width:100%; table-layout:fixed; border-collapse:collapse;">
    <colgroup>
        <col style="width:50%;">
        <col style="width:50%;">
    </colgroup>
    <tr><th colspan="2">ΟΙ ΣΥΜΒΑΛΛΟΜΕΝΟΙ</th></tr>
    <tr>
        <th>ΓΙΑ ΤΟΝ ΦΟΡΕΑ ΥΠΟΔΟΧΗΣ</th>
        <th>ΓΙΑ ΤΟ Ε.Κ.Π.Α.<br>Ο/Η Επιστημονικά/ή Υπεύθυνος/η</th>
        <!-- <th></th> -->
    </tr>
    <tr class="signature-space"><td></td><td></td></tr>
    <tr>
        <td><x-pdf.value :value="$provider->legalRepresentative()" /></td>
        <td><x-pdf.value :value="$praktiki?->details['ep_yp_ergou'] ?? null" /></td>
    </tr>

     <tr>
        <td></td>
        <td><x-pdf.value :value="$praktiki?->details['ep_yp_ergou_title'] ?? null" /></td>
    </tr>
    <tr>
        <td>(Υπογραφή - Σφραγίδα)</td>
        <td>(Υπογραφή)</td>
    </tr>

       

</table>

<table class="signatures" style="width:100%; table-layout:fixed; border-collapse:collapse;">
    <colgroup>
        <col style="width:50%;">
        <col style="width:50%;">
    </colgroup>
    <tr>
        <th>Ο/Η ΦΟΙΤΗΤΗΣ/ΤΡΙΑ</th>
        <th>ΓΙΑ ΤΟΝ Ε.Λ.Κ.Ε. Ε.Κ.Π.Α.</th></tr>
    <tr class="signature-space"><td></td><td></td></tr>
    <tr>
        <td><x-pdf.value :value="trim(($student->fName ?? '').' '.($student->lName ?? ''))" /></td>
        <td><x-pdf.value :value="$praktiki?->details['legal_representative'] ?? null" /></td>
    </tr>
     <tr>
        <td></td>
        <td><x-pdf.value :value="$praktiki?->details['legal_representative_title'] ?? null" /></td>
    </tr>
     <tr>
        <td>(Υπογραφή)</td>
        <td>(Υπογραφή)</td>
    </tr>
</table>
@endsection
