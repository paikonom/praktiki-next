<?php

namespace App\Support;

use App\Models\Student;

class StudentWorkflow
{
    /** @return array<string, array<int, array{label: string, status: int}>> */
    public static function for(Student $student, string $funding): array
    {
        $currentState = self::currentState($student);
        $items = [
            ['all', 'before', 'Εισαγωγή στοιχείων φοιτητή', (int) $student->actionIsComplete('stoix_student')],
            ['all', 'before', 'Εισαγωγή στοιχείων φορέα', (int) $student->actionIsComplete('stoix_forea')],
            ['all', 'before', 'Αντιστοίχιση στον ΑΤΛΑ', filled($student->atlas_Antistoixish) ? 1 : 0],
            ['all', 'before', 'Αρχικό συμφωνητικό χωρίς υπογραφές', (int) $student->hasCompletedDocument('symf_initial')],
            ['all', 'before', 'Ιδιωτικό συμφωνητικό (2 υπογραφές: φοιτητή και φορέα απασχόλησης)', (int) $student->hasCompletedDocument('symf_SN_f_f')],
            ['all', 'before', 'Ιδιωτικό συμφωνητικό τελική μορφή (με όλες τις υπογραφές)', (int) $student->hasCompletedDocument('symf_SN_FULL')],
            ['all', 'before', 'ΙΔΙΩΤΙΚΟ ΣΥΜΦΩΝΗΤΙΚΟ ΑΔΑ (ΔΙΑΥΓΕΙΑ)', (int) $student->hasCompletedDocument('ADA_diaugeia')],
            ['Espa', 'during', 'Απογραφικό δελτίο εισόδου', (int) $student->hasCompletedApografiko('input')],
            ['Espa', 'after', 'Απογραφικό δελτίο εξόδου', (int) $student->hasCompletedApografiko('output')],
            ['all', 'after', 'Ο φοιτητής συμπλήρωσε ηλεκτρονικά το ερωτηματολόγιο;', (int) ($student->questionnaire?->isSubmitted() ?? false)],
            ['all', 'after', 'Ο φορέας συμπλήρωσε ηλεκτρονικά το ερωτηματολόγιο;', (int) ($student->providerQuestionnaire?->isSubmitted() ?? false)],
            ['all', 'after', 'Ολοκληρώθηκε η βεβαίωση υπευθύνου τμήματος', (int) $student->hasCompletedDocument('vevaiwsh_EY')],
            ['all', 'after', 'Ολοκληρώθηκε η βεβαίωση ολοκλήρωσης από τον φορέα απασχόλησης', (int) $student->hasCompletedDocument('vevaiwsh_Forea')],
            ['all', 'after', 'Ολοκληρώθηκε η πρωτότυπη έκθεση επίδοσης από τον επιβλέποντα', (int) $student->hasCompletedDocument('ekthesh_Forea')],
            ['all', 'after', 'Ολοκληρώθηκε η πρωτότυπη αναλυτική έκθεση πεπραγμένων φοιτητή', (int) $student->hasCompletedDocument('ekthesh_Foit')],
            ['all', 'after', 'Ολοκληρώθηκε το έντυπο έναρξης ΕΡΓΑΝΗ Ε3.5', (int) $student->hasCompletedDocument('erganh_enarxh')],
            ['all', 'after', 'Ολοκληρώθηκε το έντυπο λήξης ΕΡΓΑΝΗ Ε3.5', (int) $student->hasCompletedDocument('erganh_lhxh')],
            ['InternshipProvider', 'after', 'Ολοκληρώθηκε η βεβαίωση ενσήμων e-ΕΦΚΑ από τον φοιτητή', (int) $student->hasCompletedDocument('vevaiwsh_enshmwn_foit')],
            ['InternshipProvider', 'after', 'Ολοκληρώθηκε η βεβαίωση ενσήμων από τον φορέα', (int) $student->hasCompletedDocument('vevaiwsh_enshmwn_foreas')],
            ['InternshipProvider', 'after', 'Ολοκληρώθηκε η αναλυτική κατάσταση αμοιβής από την τράπεζα', (int) $student->hasCompletedDocument('katastash_amoivhs')],
            ['all', 'after', 'Ολοκληρώθηκε το ημερήσιο πρόγραμμα παρουσίας και δραστηριοτήτων', (int) $student->hasCompletedDocument('hmer_programma')],
            ['all', 'after', 'Ολοκληρώθηκε η δήλωση στοιχείων φυσικού προσώπου', (int) $student->hasCompletedDocument('dhl_fys_pros')],
            ['all', 'after', 'Ολοκληρώθηκαν τα στοιχεία τραπεζικού λογαριασμού', (int) $student->hasCompletedDocument('bank_first')],
            ['all', 'after', 'Ολοκληρώθηκε η διαδικασία ΑΤΛΑΣ', (int) $student->hasCompletedDocument('f_atlas')],
            ['all', 'after', 'Το ΓΠΑ δήλωσε οτι έγινε η διαδικασία πληρωμής;', (int) $student->actionIsComplete('aitima_plhrwmhs')],
        ];

        $sections = ['before' => [], 'during' => [], 'after' => []];

        foreach ($items as [$itemFunding, $time, $label, $itemStatus]) {
            if ($itemFunding !== 'all' && $itemFunding !== $funding) {
                continue;
            }

            $isFutureStep = $currentState === 'before' && $time !== 'before'
                || $currentState === 'during' && $time === 'after';

            if (! $isFutureStep) {
                $sections[$time][] = ['label' => $label, 'status' => $itemStatus];
            }
        }

        return $sections;
    }

    public static function currentState(Student $student): string
    {
        if ($student->isCancelled()) {
            return 'canceled';
        }

        if ($student->isLegacy()) {
            return 'legacy';
        }

        if ($student->isCompleted()) {
            return 'completed';
        }

        if (! $student->startDate || ! $student->endDate || now()->lt($student->startDate)) {
            return 'before';
        }

        return now()->lt($student->endDate) ? 'during' : 'after';
    }
}
