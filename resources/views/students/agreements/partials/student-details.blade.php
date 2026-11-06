<table class="details">
    <tr><td>Ονοματεπώνυμο</td><td><x-agreement.value :value="trim(($student->fName ?? '').' '.($student->lName ?? ''))" /></td></tr>
    <tr><td>Ονοματεπώνυμο πατέρα</td><td><x-agreement.value :value="trim(($student->fatFName ?? '').' '.($student->fatLName ?? ''))" /></td></tr>
    <tr><td>Ονοματεπώνυμο μητέρας</td><td><x-agreement.value :value="trim(($student->momFName ?? '').' '.($student->momLName ?? ''))" /></td></tr>
    <tr><td>Ημερομηνία γέννησης</td><td><x-agreement.value :value="$student->dob ? \Illuminate\Support\Carbon::parse($student->dob)->format('d/m/Y') : null" /></td></tr>
    <tr><td>Α.Φ.Μ.</td><td><x-agreement.value :value="$student->afm" /></td></tr>
    <tr><td>Α.Μ.Κ.Α.</td><td><x-agreement.value :value="$student->amka" /></td></tr>
    <tr><td>Α.Δ.Τ.</td><td><x-agreement.value :value="$student->adt" /></td></tr>
    <tr><td>Διεύθυνση κατοικίας</td><td><x-agreement.value :value="trim(($student->address_odos_polh ?? '').' '.($student->address_odos ?? '').' '.($student->address_tk ?? ''))" /></td></tr>
    <tr><td>Τηλέφωνο / email</td><td><x-agreement.value :value="trim(($student->tel ?? '').' / '.($student->email ?? ''), ' /')" /></td></tr>
    <tr><td>Τμήμα</td><td><x-agreement.value :value="$department?->tmhma" /></td></tr>
    <tr><td>Αριθμός Μητρώου Φοιτητή</td><td><x-agreement.value :value="$student->AMF" /></td></tr>
</table>
