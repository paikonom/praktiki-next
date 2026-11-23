<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\Validator;

class RescomContract
{
    public function data(Student $student): array
    {
        $student->loadMissing(['phase.praktiki', 'provider']);
        $provider = $student->provider;
        $data = Validator::make([
            'έργο' => trim((string) data_get($student->phase?->praktiki?->details, 'K_E_ergou')),
            'έναρξη' => $student->startDate?->toDateString(),
            'λήξη' => $student->endDate?->toDateString(),
            'ποσό' => $student->payTotal,
            'ΑΦΜ φοιτητή' => $student->afm,
            'ΑΦΜ φορέα' => $provider?->f_afm,
            'ΑΜΚΑ' => $student->amka,
            'IBAN' => $student->iban,
            'ημερομηνία γέννησης' => $student->dob,
            'φύλο' => $student->fylo_rescom,
            'τύπος φορέα' => $provider?->dhm_idiwt_rescom,
        ], [
            'έργο' => 'required|regex:/^[0-9]+$/D',
            'έναρξη' => 'required|date_format:Y-m-d',
            'λήξη' => 'required|date_format:Y-m-d|after_or_equal:έναρξη',
            'ποσό' => 'required|numeric|min:0',
            'ΑΦΜ φοιτητή' => 'required|regex:/^[0-9]{9}$/D',
            'ΑΦΜ φορέα' => 'required|regex:/^[0-9]{9}$/D',
            'ΑΜΚΑ' => 'required|regex:/^[0-9]{11}$/D',
            'IBAN' => 'required',
            'ημερομηνία γέννησης' => 'required|date_format:Y-m-d',
            'φύλο' => 'required|in:1,2',
            'τύπος φορέα' => 'required|in:1,2',
        ])->validate();

        $required = [
            'φοιτητή' => [$student, ['fName', 'lName', 'doy_rescom', 'ama', 'fatFName', 'momFName', 'adt', 'numChild', 'email', 'address_odos', 'address_tk', 'address_polh_rescom', 'xwra_rescom']],
            'φορέα' => [$provider, ['f_epwnymia', 'f_doy_rescom', 'f_proeleush_rescom', 'f_odos', 'f_tk', 'f_polh_rescom', 'f_xwra_rescom', 'f_thl']],
        ];
        $errors = [];
        foreach ($required as $role => [$record, $fields]) {
            foreach ($fields as $field) {
                if (blank($record?->{$field})) {
                    $errors[] = "Λείπει το πεδίο {$field} ({$role}).";
                }
            }
        }
        if ($errors !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages(['rescom' => $errors]);
        }

        $male = (int) $student->fylo_rescom === 1;

        return [
            'Πρωτοκόλλου_Αριθμός' => '',
            'Πρωτοκόλλου_Χρήση' => '',
            'Ημερομηνία_Έναρξης' => $data['έναρξη'].' 00:00:00',
            'Ημερομηνία_Λήξης' => $data['λήξη'].' 00:00:00',
            'Συνολικό_Ποσό' => (string) $student->payTotal,
            'Τύπου_Κωδικός' => '33',
            'Ειδικότητας_Ασφαλισμένου_ΙΚΑ_Κωδικός' => '332020',
            'Ερευνητικής_Ομάδας_Κωδικός' => '',
            'Ειδικότερη_Απασχόληση' => 'Πρακτική Άσκηση Φοιτητή στον Φορέα Υποδοχής '.$provider->f_epwnymia,
            'Κατηγορίας_Δαπάνης_Κωδικός' => '791',
            'Μορφωτικού_Επιπέδου_Κωδικός' => '1',
            'Έργου_Αριθμός' => $data['έργο'],
            'Φορέα_Πρακτικής_Άσκησης_Κωδικός' => '',
            'Φορέας_Πρακτικής_Άσκησης' => [
                'Επωνυμία' => $provider->f_epwnymia,
                'Αριθμός_Φορολογικού_Μητρώου' => (string) $provider->f_afm,
                'Τύπου_Κωδικός' => '2',
                'Δημόσιας_Οικονομικής_Υπηρεσίας_Κωδικός' => (string) $provider->f_doy_rescom,
                'Οργανισμός' => [
                    'Δραστηριότητα' => '',
                    'Δημόσιο' => (int) $provider->dhm_idiwt_rescom === 1 ? 'true' : 'false',
                    'Προέλευσης_Κωδικός' => (string) $provider->f_proeleush_rescom,
                ],
                'Διευθύνσεις' => [$this->address($provider->f_odos, $provider->f_tk, $provider->f_polh_rescom, $provider->f_xwra_rescom)],
                'Τηλέφωνα' => [['Αριθμός' => $provider->f_thl, 'Τύπου_Κωδικός' => '110']],
            ],
            'Αποδέκτης' => [
                'Επωνυμία' => $student->lName,
                'Τύπου_Κωδικός' => '3',
                'Δημόσιας_Οικονομικής_Υπηρεσίας_Κωδικός' => (string) $student->doy_rescom,
                'IBAN' => $student->iban,
                'Αριθμός_Φορολογικού_Μητρώου' => (string) $student->afm,
                'Στοιχεία_Ασφάλισης' => ['Αριθμός_Μητρώου_ΙΚΑ' => $student->ama],
                'Πρόσωπο_Φυσικό' => [
                    'Όνομα' => $student->fName,
                    'Όνομα_Πατέρα' => $student->fatFName,
                    'Όνομα_Μητέρας' => $student->momFName,
                    'Αριθμός_Αστυνομικής_Ταυτότητας' => $student->adt,
                    'Αριθμός_Διαβατηρίου' => '',
                    'Είναι_άντρας' => $male ? 'true' : 'false',
                    'Είναι_έγγαμος' => in_array($student->oikKat, ['ΑΓΑΜΟΣ', 'ΑΓΑΜΗ'], true) ? 'false' : 'true',
                    'Αριθμός_Παιδιών' => $student->numChild,
                    'Ημερομηνία_Γέννησης' => $data['ημερομηνία γέννησης'].' 00:00:00',
                    'Αριθμός_Μητρώου_Κοινωνικής_Ασφάλισης' => $student->amka,
                    'Επαγγέλματος_Κωδικός' => $male ? '7617' : '7773',
                ],
                'Διευθύνσεις_Ηλεκτρονικές' => [['Περιγραφή' => $student->email, 'Τύπου_Κωδικός' => '31']],
                'Διευθύνσεις' => [$this->address($student->address_odos, $student->address_tk, $student->address_polh_rescom, $student->xwra_rescom)],
            ],
        ];
    }

    private function address(string $street, string|int $postalCode, string|int $city, string|int $country): array
    {
        return [
            'Περιγραφή' => $street,
            'Ταχυδρομικός_Κωδικός' => (string) $postalCode,
            'Τύπου_Κωδικός' => '61',
            'Πόλης_Κωδικός' => (string) $city,
            'Χώρας_Κωδικός' => (string) $country,
        ];
    }
}
