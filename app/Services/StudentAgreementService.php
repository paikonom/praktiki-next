<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class StudentAgreementService
{
    /** @return array{view: string, data: array<string, mixed>, missing: Collection<int, string>} */
    public function build(Student $student): array
    {
        $this->loadAgreementRelations($student);

        $funding = $student->fundingCode();
        $praktiki = $student->phase?->praktiki;
        $phase = $student->phase;
        if (! in_array($funding, ['Espa', 'Institution', 'InternshipProvider'], true)) {
            throw new InvalidArgumentException('Δεν υποστηρίζεται η χρηματοδότηση της πρακτικής.');
        }

        $details = $praktiki?->details ?? [];
        $phaseDetails = $phase?->details ?? [];

        $data = [
            'student' => $student,
            'provider' => $student->provider,
            'department' => $student->department,
            'phase' => $phase,
            'phaseDetails' => $phaseDetails,
            'praktiki' => $praktiki,
            'details' => $details,
            'supervisor' => $student->supervisor,
            'funding' => $funding,
        ];

        return [
            'view' => 'students.agreements.'.match ($funding) {
                'Espa' => 'espa',
                'Institution' => 'institution',
                'InternshipProvider' => 'internship-provider',
            },
            'data' => $data,
            'missing' => $this->missingFields($funding, $data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, string>
     */
    private function missingFields(string $funding, array $data): Collection
    {
        $student = $data['student'];
        $provider = $data['provider'];
        $department = $data['department'];
        $phaseDetails = $data['phaseDetails'];
        $details = $data['details'];
        $supervisor = $data['supervisor'];

        $fields = [
            'Όνομα φοιτητή' => $student->fName,
            'Επώνυμο φοιτητή' => $student->lName,
            'Αριθμός μητρώου φοιτητή' => $student->AMF,
            'ΑΦΜ φοιτητή' => $student->afm,
            'ΑΜΚΑ φοιτητή' => $student->amka,
            'Ημερομηνία έναρξης' => $student->startDate,
            'Ημερομηνία λήξης' => $student->endDate,
            'Τμήμα' => $department?->tmhma,
            'Πόλη υπογραφής' => $department?->polh,
            'Επωνυμία φορέα' => $provider?->f_epwnymia,
            'ΑΦΜ φορέα' => $provider?->f_afm,
            'Έδρα φορέα' => $provider?->headquartersAddress(),
            'Τόπος εκτέλεσης πρακτικής' => $provider?->internshipAddress(),
            'Νόμιμος εκπρόσωπος φορέα' => trim(($provider?->f_ekprFname ?? '').' '.($provider?->f_ekprLname ?? '')),
            'Επόπτης φορέα' => trim(($provider?->f_epopt_foreaFname ?? '').' '.($provider?->f_epopt_foreaLname ?? '')),
            'Αρχικός Επιστημονικός Υπεύθυνος' => trim(($supervisor?->fName ?? '').' '.($supervisor?->lName ?? '')),
        ];

        if ($funding === 'Espa') {
            $fields += [
                'Επιστημονικός Υπεύθυνος έργου' => $details['ep_yp_ergou'] ?? null,
                'Ιδιότητα επιστημονικού υπεύθυνου έργου' => $details['ep_yp_ergou_title'] ?? null,
                'Κωδικός έργου Κ.Ε.' => $details['K_E_ergou'] ?? null,
                'Τίτλος υποέργου' => $details['titlos_ypoergou'] ?? null,
                'Κωδικός ΟΠΣ' => $details['kwdikos_OPS'] ?? null,
                'Απόφαση Τμήματος' => $phaseDetails['apofash_tmhmatos'] ?? null,
                'Απόφαση ΕΛΚΕ' => $phaseDetails['apofash_ELKE'] ?? null,
                'ΑΔΑ απόφασης ΕΛΚΕ' => $phaseDetails['ADA_apofash_ELKE'] ?? null,
                'Συνολικό κόστος πρακτικής' => $student->payTotal,
                'Αριθμός δόσεων' => $student->doseis_count,
                'Νόμιμος εκπρόσωπος ΕΛΚΕ' => $details['legal_representative'] ?? null,
                'Ιδιότητα νόμιμου εκπροσώπου ΕΛΚΕ' => $details['legal_representative_title'] ?? null,
            ];
        }

        if ($funding === 'Institution') {
            $fields += [
                'Ιδρυματικός Υπεύθυνος έργου' => $details['ep_yp_ergou'] ?? null,
                'Ιδιότητα επιστημονικού υπεύθυνου έργου' => $details['ep_yp_ergou_title'] ?? null,
                'Κωδικός έργου Κ.Ε.' => $details['K_E_ergou'] ?? null,
                'Εγκριτική απόφαση ΕΛΚΕ' => $phaseDetails['apofash_ELKE'] ?? null,
                'Ώρες ανά εβδομάδα' => $student->wresPerWeek,
                'Νόμιμος εκπρόσωπος ΕΛΚΕ' => $details['legal_representative'] ?? null,
                'Ιδιότητα νόμιμου εκπροσώπου ΕΛΚΕ' => $details['legal_representative_title'] ?? null,
            ];
        }

        if ($funding === 'InternshipProvider') {
            $fields += [
                'Απόφαση Τμήματος' => $phaseDetails['apofash_tmhmatos'] ?? null,
                'Αντικείμενο πρακτικής' => $provider?->f_antikeim_Praktikhs,
                'Αναμενόμενα αποτελέσματα' => $provider?->f_apotel_Praktikhs,
                'Θέση επόπτη φορέα' => $provider?->f_epopt_foreaThesh,
                'Ώρες ανά ημέρα' => $student->wresPerDays,
                'Συνολική αμοιβή' => $student->payTotal,
                'Μηνιαία αμοιβή' => $student->payPerMonth,
            ];
        }

        return collect($fields)
            ->filter(fn (mixed $value): bool => blank($value))
            ->keys()
            ->values();
    }

    private function loadAgreementRelations(Student $student): void
    {
        $student->loadMissing([
            'department',
            'provider',
            'actions',
            'supervisor',
            'phase.praktiki.funding',
        ]);
    }
}
