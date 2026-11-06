<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->string('card_section', 20);
            $table->string('field', 100);
            $table->json('fundings');
            $table->json('visible_to_roles');
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->string('section')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['card_section', 'field']);
            $table->index(['card_section', 'active', 'sort_order']);
        });

        // Initial data only. After installation, the database is the source of truth.
        $fields = [
            ['office', 'stoix_student', '["all"]', '["member"]', 'ΣΤΟΙΧΕΙΑ ΦΟΙΤΗΤΗ', null, 'Κατάσταση διαδικασίας', 1],
            ['office', 'stoix_forea', '["all"]', '["member"]', 'ΣΤΟΙΧΕΙΑ ΦΟΡΕΑ', null, 'Κατάσταση διαδικασίας', 2],
            ['office', 'all_files_foit', '["all"]', '["member"]', 'ΕΝΗΜΕΡΩΣΗ ΑΡΧΕΙΩΝ ΦΟΙΤΗΤΗ', null, 'Κατάσταση διαδικασίας', 3],
            ['office', 'all_files_foreas', '["all"]', '["member"]', 'ΕΝΗΜΕΡΩΣΗ ΑΡΧΕΙΩΝ ΦΟΡΕΑ', null, 'Κατάσταση διαδικασίας', 4],
            ['office', 'all_files_scientific_supervisor', '["all"]', '["member"]', 'ΕΝΗΜΕΡΩΣΗ ΑΡΧΕΙΩΝ ΕΠ/ΥΠ', null, 'Κατάσταση διαδικασίας', 5],
            ['office', 'finish_pa', '["all"]', '["member"]', 'ΟΛΟΚΛΗΡΩΣΗ ΠΡΑΚΤΙΚΗΣ', null, 'Κατάσταση διαδικασίας', 6],
            ['office', 'aitima_plhrwmhs', '["all"]', '["member"]', 'ΑΙΤΗΜΑ ΠΛΗΡΩΜΗΣ', null, 'Κατάσταση διαδικασίας', 7],
            ['office', 'status', '["all"]', '["member"]', 'ΚΑΤΑΣΤΑΣΗ ΠΡΑΚΤΙΚΗΣ', null, 'Κατάσταση διαδικασίας', 8],

            ['provider', 'f_epwnymia', '["all"]', '["all"]', 'ΕΠΩΝΥΜΙΑ Φ.Υ.', 'Μόνο κεφαλαία γράμματα.', 'Ταυτότητα φορέα', 52],
            ['provider', 'f_nomikhMorfh_rescom', '["all"]', '["all"]', 'ΝΟΜΙΚΗ ΜΟΡΦΗ', null, 'Ταυτότητα φορέα', 53],
            ['provider', 'f_doy_rescom', '["all"]', '["member","student","provider"]', 'ΔΟΥ Φ.Υ.', 'Επιλέξτε ΔΟΥ.', 'Έδρα και επικοινωνία', 54],
            ['provider', 'dhm_idiwt_rescom', '["all"]', '["member","student","provider"]', 'ΔΗΜΟΣΙΟΣ-ΙΔΙΩΤΙΚΟΣ', null, 'Ταυτότητα φορέα', 55],
            ['provider', 'f_proeleush_rescom', '["all"]', '["member","student","provider"]', 'ΠΡΟΕΛΕΥΣΗ ΦΟΡΕΑ', null, 'Ταυτότητα φορέα', 56],
            ['provider', 'f_afm', '["all"]', '["member","student","provider"]', 'ΑΦΜ Φ.Υ.', '7 ή 9 αριθμοί.', 'Ταυτότητα φορέα', 57],
            ['provider', 'f_perioxh', '["all"]', '["member"]', 'ΕΔΡΑ Φ.Υ.: ΠΟΛΗ', 'Πληκτρολογήστε πόλη και περιοχή.', 'Έδρα και επικοινωνία', 58],
            ['provider', 'f_polh_rescom', '["all"]', '["all"]', 'ΕΔΡΑ Φ.Υ.: ΠΟΛΗ', 'Επιλέξτε πόλη.', 'Έδρα και επικοινωνία', 59],
            ['provider', 'f_xwra_rescom', '["all"]', '["all"]', 'ΧΩΡΑ Φ.Υ.', 'Επιλέξτε χώρα.', 'Έδρα και επικοινωνία', 60],
            ['provider', 'f_odos', '["all"]', '["all"]', 'ΕΔΡΑ Φ.Υ.: ΟΔΟΣ & ΑΡΙΘΜΟΣ', 'Πληκτρολογήστε οδό και αριθμό.', 'Έδρα και επικοινωνία', 61],
            ['provider', 'f_tk', '["all"]', '["all"]', 'ΕΔΡΑ Φ.Υ.: ΤΚ', '5 ψηφία.', 'Έδρα και επικοινωνία', 62],
            ['provider', 'f_perifereia', '["all"]', '["member"]', 'ΠΕΡΙΦΕΡΕΙΑ ΥΛΟΠΟΙΗΣΗΣ', null, 'Έδρα και επικοινωνία', 63],
            ['provider', 'f_thl', '["all"]', '["all"]', 'ΤΗΛΕΦΩΝΟ ΕΠΙΚΟΙΝΩΝΙΑΣ Φ.Υ.', null, 'Έδρα και επικοινωνία', 64],
            ['provider', 'f_email', '["all"]', '["all"]', 'EMAIL Φ.Υ.', 'Προτιμήστε ένα email στο οποίο έχετε άμεση πρόσβαση.', 'Έδρα και επικοινωνία', 65],
            ['provider', 'f_ekprFname', '["all"]', '["member","student","provider"]', 'ΟΝΟΜΑ ΝΟΜΙΜΟΥ ΕΚΠΡΟΣΩΠΟΥ ΤΟΥ Φ.Υ.', 'Μόνο κεφαλαία γράμματα.', 'Νόμιμος εκπρόσωπος', 66],
            ['provider', 'f_ekprLname', '["all"]', '["member","student","provider"]', 'ΕΠΩΝΥΜΟ ΝΟΜΙΜΟΥ ΕΚΠΡΟΣΩΠΟΥ ΤΟΥ Φ.Υ.', 'Μόνο κεφαλαία γράμματα.', 'Νόμιμος εκπρόσωπος', 67],
            ['provider', 'f_ekprIdiothta', '["all"]', '["member","student","provider"]', 'ΙΔΙΟΤΗΤΑ ΝΟΜΙΜΟΥ ΕΚΠΡΟΣΩΠΟΥ', 'Μόνο κεφαλαία γράμματα. Συμπληρώνεται αν πρόκειται για νομικό πρόσωπο.', 'Νόμιμος εκπρόσωπος', 68],
            ['provider', 'f_ektPrakt_polh_rescom', '["all"]', '["all"]', 'ΠΟΛΗ ΕΚΤΕΛΕΣΗΣ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ', 'Επιλέξτε την πόλη όπου θα πραγματοποιηθεί η πρακτική.', 'Τόπος εκτέλεσης πρακτικής', 69],
            ['provider', 'f_ektPrakt_odos', '["all"]', '["all"]', 'ΟΔΟΣ ΚΑΙ ΑΡΙΘΜΟΣ ΕΚΤΕΛΕΣΗΣ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ', 'Γράψτε την οδό και τον αριθμό όπου θα πραγματοποιηθεί η πρακτική.', 'Τόπος εκτέλεσης πρακτικής', 70],
            ['provider', 'f_topos_ekt_prakt', '["all"]', '["member"]', 'ΤΟΠΟΣ ΕΚΤΕΛΕΣΗΣ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ', null, 'Έδρα και επικοινωνία', 71],
            ['provider', 'f_ektPrakt_tk', '["all"]', '["all"]', 'ΤΚ ΕΚΤΕΛΕΣΗΣ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ', '5 ψηφία.', 'Τόπος εκτέλεσης πρακτικής', 71],
            ['provider', 'f_epopt_foreaFname', '["all"]', '["all"]', 'ΟΝΟΜΑ ΕΠΟΠΤΗ ΤΟΥ Φ.Υ.', 'Μόνο κεφαλαία γράμματα.', 'Επόπτης φορέα', 72],
            ['provider', 'f_epopt_foreaLname', '["all"]', '["all"]', 'ΕΠΩΝΥΜΟ ΕΠΟΠΤΗ ΤΟΥ Φ.Υ.', 'Μόνο κεφαλαία γράμματα.', 'Επόπτης φορέα', 73],
            ['provider', 'f_epopt_email', '["all"]', '["all"]', 'EMAIL ΕΠΟΠΤΗ ΤΟΥ Φ.Υ.', null, 'Επόπτης φορέα', 74],
            ['provider', 'f_epopt_foreaThesh', '["all"]', '["all"]', 'ΘΕΣΗ ΕΠΟΠΤΗ ΤΟΥ Φ.Υ.', null, 'Επόπτης φορέα', 75],
            ['provider', 'f_epopt_foreaKatartish', '["all"]', '["all"]', 'ΚΑΤΑΡΤΙΣΗ-ΕΜΠΕΙΡΙΑ ΕΠΟΠΤΗ ΤΟΥ Φ.Υ.', null, 'Επόπτης φορέα', 76],
            ['provider', 'f_antikeim_Praktikhs', '["InternshipProvider"]', '["all"]', 'ΑΝΤΙΚΕΙΜΕΝΟ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ', null, 'Περιγραφή πρακτικής', 77],
            ['provider', 'f_apotel_Praktikhs', '["InternshipProvider"]', '["all"]', 'ΑΠΟΤΕΛΕΣΜΑΤΑ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ', null, 'Περιγραφή πρακτικής', 78],

            ['student', 'fName', '["all"]', '["all"]', 'ΟΝΟΜΑ', 'Μόνο κεφαλαία γράμματα.', 'Βασικά στοιχεία', 1],
            ['student', 'lName', '["all"]', '["all"]', 'ΕΠΩΝΥΜΟ', 'Μόνο κεφαλαία γράμματα.', 'Βασικά στοιχεία', 2],
            ['student', 'email', '["all"]', '["all"]', 'EMAIL', 'Προτιμήστε ένα email στο οποίο έχετε άμεση πρόσβαση.', 'Βασικά στοιχεία', 3],
            ['student', 'tel', '["all"]', '["all"]', 'ΚΙΝΗΤΟ ΤΗΛΕΦΩΝΟ', '10 ψηφία και πρέπει να ξεκινά από 69.', 'Βασικά στοιχεία', 4],
            ['student', 'AMF', '["all"]', '["all"]', 'ΑΡΙΘΜΟΣ ΜΗΤΡΩΟΥ ΦΟΙΤΗΤΗ', null, 'Βασικά στοιχεία', 5],
            ['student', 'amka', '["all"]', '["member","student"]', 'ΑΜΚΑ', '11 ψηφία.', 'Ταυτοποίηση', 6],
            ['student', 'afm', '["all"]', '["member","student"]', 'ΑΦΜ', '9 ψηφία.', 'Ταυτοποίηση', 7],
            ['student', 'id_group', '["all"]', '["member","student"]', 'GROUP ATLAS', 'Ρωτήστε τον φορέα σας.', 'Εσωτερικά στοιχεία', 8],
            ['student', 'startDate', '["all"]', '["all"]', 'ΗΜΕΡΟΜΗΝΙΑ ΕΝΑΡΞΗΣ', 'Ρωτήστε τον φορέα σας. Η έναρξη θα πρέπει να είναι τουλάχιστον 15 ημέρες μετά τη σημερινή ημερομηνία.', 'Στοιχεία πρακτικής', 9],
            ['student', 'endDate', '["all"]', '["all"]', 'ΗΜΕΡΟΜΗΝΙΑ ΛΗΞΗΣ', 'Ρωτήστε τον φορέα σας και ελέγξτε το email που σας έχει σταλεί.', 'Στοιχεία πρακτικής', 10],
            ['student', 'ama', '["all"]', '["member","student"]', 'ΑΡΙΘΜΟΣ ΜΗΤΡΩΟΥ ΑΣΦΑΛΙΣΜΕΝΟΥ (ΑΜΑ)', 'Αριθμός ΕΦΚΑ (πρώην ΑΜΑ ΙΚΑ), 7 ή 9 ψηφία.', 'Ταυτοποίηση', 11],
            ['student', 'iban', '["all"]', '["member","student"]', 'ΙΒΑΝ', 'GR και 25 ψηφία, χωρίς κενά.', 'Οικονομικά', 13],
            ['student', 'adt', '["all"]', '["member","student"]', 'ΑΡΙΘΜΟΣ ΔΕΛΤΙΟΥ ΤΑΥΤΟΤΗΤΑΣ', 'Χωρίς κενά: 1–2 γράμματα και 6–8 αριθμοί, π.χ. ΑΒ123456.', 'Ταυτοποίηση', 14],
            ['student', 'fylo_rescom', '["all"]', '["member","student"]', 'ΦΥΛΟ', 'Επιλέξτε φύλο.', 'Ταυτοποίηση', 14],
            ['student', 'adt_ek_arxh', '["all"]', '["member","student"]', 'ΕΚΔΟΥΣΑ ΑΡΧΗ ΤΑΥΤΟΤΗΤΑΣ', 'Μόνο κεφαλαία γράμματα.', 'Ταυτοποίηση', 15],
            ['student', 'adt_ek_date', '["all"]', '["member","student"]', 'ΗΜΕΡΟΜΗΝΙΑ ΕΚΔΟΣΗΣ ΤΑΥΤΟΤΗΤΑΣ', null, 'Ταυτοποίηση', 16],
            ['student', 'fatFName', '["all"]', '["member","student"]', 'ΟΝΟΜΑ ΠΑΤΕΡΑ', 'Μόνο κεφαλαία γράμματα.', 'Οικογενειακά και εκπαίδευση', 17],
            ['student', 'fatLName', '["all"]', '["member","student"]', 'ΕΠΩΝΥΜΟ ΠΑΤΕΡΑ', 'Μόνο κεφαλαία γράμματα.', 'Οικογενειακά και εκπαίδευση', 18],
            ['student', 'momFName', '["all"]', '["member","student"]', 'ΟΝΟΜΑ ΜΗΤΕΡΑΣ', 'Μόνο κεφαλαία γράμματα.', 'Οικογενειακά και εκπαίδευση', 19],
            ['student', 'momLName', '["all"]', '["member","student"]', 'ΕΠΩΝΥΜΟ ΜΗΤΕΡΑΣ', 'Μόνο κεφαλαία γράμματα.', 'Οικογενειακά και εκπαίδευση', 20],
            ['student', 'katEkp_rescom', '["all"]', '["member","student"]', 'ΚΑΤΗΓΟΡΙΑ ΕΚΠΑΙΔΕΥΣΗΣ', 'Αν δεν έχετε άλλο πτυχίο, επιλέξτε Δευτεροβάθμια Εκπαίδευση.', 'Οικογενειακά και εκπαίδευση', 21],
            ['student', 'oikKat', '["all"]', '["member","student"]', 'ΟΙΚΟΓΕΝΕΙΑΚΗ ΚΑΤΑΣΤΑΣΗ', 'Επιλέξτε την οικογενειακή σας κατάσταση.', 'Οικογενειακά και εκπαίδευση', 22],
            ['student', 'syzName', '["all"]', '["member","student"]', 'ΟΝΟΜΑ ΣΥΖΥΓΟΥ', 'Εάν δεν έχετε σύζυγο, πληκτρολογήστε -.', 'Οικογενειακά και εκπαίδευση', 23],
            ['student', 'numChild', '["all"]', '["member","student"]', 'ΑΡΙΘΜΟΣ ΠΑΙΔΙΩΝ', 'Γράψτε 0 αν δεν έχετε παιδιά.', 'Οικογενειακά και εκπαίδευση', 24],
            ['student', 'doy_rescom', '["all"]', '["member","student"]', 'ΔΟΥ', null, 'Διεύθυνση', 25],
            ['student', 'dob', '["all"]', '["member","student"]', 'ΗΜΕΡΟΜΗΝΙΑ ΓΕΝΝΗΣΗΣ', null, 'Ταυτοποίηση', 26],
            ['student', 'placeDob', '["all"]', '["member","student"]', 'ΤΟΠΟΣ ΓΕΝΝΗΣΗΣ', 'Μόνο κεφαλαία γράμματα.', 'Ταυτοποίηση', 27],
            ['student', 'ithagen', '["all"]', '["member","student"]', 'ΙΘΑΓΕΝΕΙΑ', 'Π.χ. ΕΛΛΗΝΙΚΗ.', 'Διεύθυνση', 28],
            ['student', 'xwra_rescom', '["all"]', '["member","student"]', 'ΧΩΡΑ ΚΑΤΟΙΚΙΑΣ', null, 'Διεύθυνση', 29],
            ['student', 'address_polh_rescom', '["all"]', '["member","student"]', 'ΠΟΛΗ', null, 'Διεύθυνση', 30],
            ['student', 'address_odos', '["all"]', '["member","student"]', 'ΔΙΕΥΘΥΝΣΗ: ΟΔΟΣ & ΑΡΙΘΜΟΣ', 'Μόνο κεφαλαία γράμματα.', 'Διεύθυνση', 31],
            ['student', 'address_odos_polh', '["all"]', '["member","student"]', 'ΔΙΕΥΘΥΝΣΗ: ΠΟΛΗ', 'Η πόλη πρέπει να αντιστοιχεί στη διεύθυνση που θα δηλώσετε.', 'Διεύθυνση', 32],
            ['student', 'address_tk', '["all"]', '["member","student"]', 'ΔΙΕΥΘΥΝΣΗ: ΤΑΧΥΔΡΟΜΙΚΟΣ ΚΩΔΙΚΑΣ', '5 ψηφία.', 'Διεύθυνση', 33],
            ['student', 'workDaysDescription', '["all"]', '["all"]', 'ΗΜΕΡΕΣ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ', 'Π.χ. ΔΕΥΤΕΡΑ ΕΩΣ ΠΑΡΑΣΚΕΥΗ.', 'Στοιχεία πρακτικής', 35],
            ['student', 'merikhsPlhrhs', '["all"]', '["member"]', 'ΜΕΡΙΚΗ - ΠΛΗΡΗΣ ΑΠΑΣΧΟΛΗΣΗ', null, 'Στοιχεία πρακτικής', 36],
            ['student', 'payPerMonth', '["all"]', '["member","student"]', 'ΜΗΝΙΑΙΑ ΑΠΟΖΗΜΙΩΣΗ', 'Ποσό σε ευρώ.', 'Οικονομικά', 37],
            ['student', 'payTotal', '["all"]', '["member","student"]', 'ΣΥΝΟΛΙΚΟ ΚΟΣΤΟΣ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ', 'Συνολικό ποσό σε ευρώ.', 'Οικονομικά', 38],
            ['student', 'doseis_count', '["all"]', '["member"]', 'ΠΛΗΘΟΣ ΔΟΣΕΩΝ', null, 'Οικονομικά', 39],
            ['student', 'numMhnes', '["all"]', '["all"]', 'ΑΡΙΘΜΟΣ ΜΗΝΩΝ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ', 'Επιλέξτε τη διάρκεια.', 'Στοιχεία πρακτικής', 40],
            ['student', 'diakoptomeno', '["all"]', '["member"]', 'ΔΙΑΚΟΠΤΟΜΕΝΟ ΔΙΑΣΤΗΜΑ;', null, 'Στοιχεία πρακτικής', 41],
            ['student', 'endDate_DKPT_A', '["all"]', '["member"]', 'ΛΗΞΗ 1ου ΔΙΑΚΟΠΤΟΜΕΝΟΥ ΔΙΑΣΤΗΜΑΤΟΣ', null, 'Στοιχεία πρακτικής', 42],
            ['student', 'startDate_DKPT_B', '["all"]', '["member"]', 'ΕΝΑΡΞΗ 2ου ΔΙΑΚΟΠΤΟΜΕΝΟΥ ΔΙΑΣΤΗΜΑΤΟΣ', null, 'Στοιχεία πρακτικής', 43],
            ['student', 'epopt_kath', '["all"]', '["all"]', 'ΕΠΟΠΤΗΣ ΚΑΘΗΓΗΤΗΣ ΤΜΗΜΑΤΟΣ', null, 'Στοιχεία πρακτικής', 44],
            ['student', 'praxh_year', '["all"]', '["member"]', 'ΠΡΑΞΗ - ΕΤΟΣ', null, 'Εσωτερικά στοιχεία', 46],
            ['student', 'questionnaire_version', '["all"]', '["member"]', 'ΕΚΔΟΣΗ ΕΡΩΤΗΜΑΤΟΛΟΓΙΟΥ', null, 'Εσωτερικά στοιχεία', 47],
            ['student', 'notes', '["all"]', '["member"]', 'ΣΗΜΕΙΩΣΕΙΣ', null, 'Εσωτερικά στοιχεία', 49],
            ['student', 'wresPerDays', '["InternshipProvider"]', '["all"]', 'ΩΡΕΣ ΗΜΕΡΗΣΙΩΣ', 'Από 1 έως 8.', 'Στοιχεία πρακτικής', 50],
            ['student', 'wresPerWeek', '["Espa","Institution"]', '["all"]', 'ΩΡΕΣ ΤΗΝ ΕΒΔΟΜΑΔΑ', 'Από 1 έως 40.', 'Στοιχεία πρακτικής', 51],
        ];

        $now = now();
        DB::table('fields')->insert(collect($fields)->map(fn (array $field): array => [
            'card_section' => $field[0],
            'field' => $field[1],
            'fundings' => $field[2],
            'visible_to_roles' => $field[3],
            'label' => $field[4],
            'help_text' => $field[5],
            'section' => $field[6],
            'sort_order' => $field[7],
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
