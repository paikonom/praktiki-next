<?php

namespace App\Services;

use App\Models\Student;
use App\Models\WorkflowAction;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StudentEmailService
{
    private const EMAILS = [
        'stoixeiaFoit' => ['title' => 'Φοιτητής: Εισαγωγή Στοιχείων Συμφωνητικού', 'button' => 'Στχ. Φ.', 'recipient_role' => 'student'],
        'stoixeiaFoitEgkrish' => ['title' => 'Φοιτητής: Υπογραφή & Επισύναψη Συμφωνητικού', 'button' => '2 Υπογραφές', 'recipient_role' => 'student'],
        'symf_SN_f_f_ey' => ['title' => 'ΕΠ.ΥΠ.: Υπογραφή Συμφωνητικού', 'button' => '3 Υπογραφές', 'recipient_role' => 'supervisor'],
        'symf_SN_FULL' => ['title' => 'Φοιτητής: Λήψη Συμφωνητικού & ΑΔΑ', 'button' => 'ΛΗΨΗ ΣΥΜΦ - ΑΔΑ', 'recipient_role' => 'student'],
        'erganh_enarxh' => ['title' => 'Φορέας: Λήψη Συμφωνητικού & ΑΔΑ - Οδηγίες Ε3.5 ΕΡΓΑΝΗ', 'button' => 'ΕΡΓΑΝΗ', 'recipient_role' => 'provider'],
        'input' => ['title' => 'Φοιτητής: Υποβολή Απογραφικού Δελτίου Εισόδου', 'button' => 'Input', 'recipient_role' => 'student'],
        'lhxh' => ['title' => 'Φοιτητής: Εκκρεμότητες λήξης', 'button' => 'ΛΗΞΗ ΦΟΙΤ.', 'recipient_role' => 'student'],
        'lhxh_foreas' => ['title' => 'Φορέας: Εκκρεμότητες λήξης', 'button' => 'ΛΗΞΗ ΦΟΡΕΑΣ', 'recipient_role' => 'provider'],
        'lhxh_ey' => ['title' => 'ΕΠ.ΥΠ.: Εκκρεμότητες λήξης', 'button' => 'ΛΗΞΗ ΕΠ.ΥΠ.', 'recipient_role' => 'supervisor'],
    ];

    /** @return Collection<int, array<string, mixed>> */
    public function entries(Student $student): Collection
    {
        $counts = $student->emailLogs->where('status', 'sent')->countBy('type');

        return collect(self::EMAILS)->map(function (array $definition, string $type) use ($student, $counts): array {
            return $definition + [
                'type' => $type,
                'count' => (int) $counts->get($type, 0),
                'unavailable_reason' => $this->unavailableReason($student, $type),
            ];
        })->values();
    }

    /** @return array{type: string, subject: string, to: array<int, string>, cc: array<int, string>, data: array<string, mixed>} */
    public function prepare(Student $student, string $type): array
    {
        $definition = self::EMAILS[$type] ?? throw ValidationException::withMessages(['email' => 'Άγνωστος τύπος email.']);
        $to = $this->recipientAddresses($student, $definition['recipient_role']);
        $handler = $student->handler();

        if ($to === []) {
            throw ValidationException::withMessages(['email' => 'Δεν υπάρχει έγκυρη διεύθυνση παραλήπτη.']);
        }

        if (! $handler) {
            throw ValidationException::withMessages(['email' => 'Δεν βρέθηκε μοναδικός χειριστής για το Τμήμα και τη χρηματοδότηση.']);
        }

        $name = trim("{$student->fName} {$student->lName}");
        $subject = match ($type) {
            'stoixeiaFoit' => "Εισαγωγή Στοιχείων Συμφωνητικού. ΦΑΣΗ {$student->phase?->name}",
            'stoixeiaFoitEgkrish' => "Υπογραφή & Επισύναψη Συμφωνητικού - {$name}",
            'symf_SN_f_f_ey' => "Υπογραφή (3η) Συμφωνητικού - {$name}",
            'symf_SN_FULL' => "ΛΗΨΗ Συμφωνητικού & ΑΔΑ - {$name}",
            'erganh_enarxh' => "ΛΗΨΗ Συμφωνητικού & ΑΔΑ - ΟΔΗΓΙΕΣ ΣΥΜΠΛΗΡΩΣΗΣ Ε3.5 ΕΡΓΑΝΗ - {$name}",
            'input' => "Υποβολή Απογραφικού Δελτίου Εισόδου - {$name}",
            'lhxh' => "Εκκρεμότητες Λήξης ΦΟΙΤΗΤΗ/ΤΡΙΑΣ - {$name}",
            'lhxh_foreas' => "Εκκρεμότητες Λήξης ΦΟΡΕΑ ΥΠΟΔΟΧΗΣ - {$name}",
            'lhxh_ey' => "Εκκρεμότητες Λήξης ΕΠ.ΥΠ. - {$name}",
        };

        $cc = collect([$handler->email, 'paoikon@uoa.gr'])
            ->filter(fn ($email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->diff($to)
            ->unique()
            ->values()
            ->all();

        return [
            'type' => $type,
            'subject' => $subject,
            'to' => $to,
            'cc' => $cc,
            'data' => compact('type', 'student', 'handler'),
        ];
    }

    /** @param Collection<int, WorkflowAction> $pendingActions */
    public function typeForPendingActions(string $section, Collection $pendingActions): ?string
    {
        $pendingCodes = $pendingActions->pluck('code');
        // echo $pendingCodes;

        if ($pendingCodes->intersect(['stoix_student', 'stoix_forea'])->isNotEmpty()) {
            return 'stoixeiaFoit';
        }

        if ($pendingCodes->contains('symf_sgn_foit_foreas')) {
            return 'stoixeiaFoitEgkrish';
        }

        if ($pendingCodes->contains('symf_sgn_foit_foreas_scientific_supervisor')) {
            return 'symf_SN_f_f_ey';
        }

        if ($pendingCodes->contains('erganh_enarxh_upload')) {
            return 'erganh_enarxh';
        }

        if ($pendingCodes->contains('input_sumpl')) {
            return 'input';
        }

        if ($section === 'G' && $pendingActions->contains('responsible_role', 'student')) {
            return 'lhxh';
        }

        if ($section === 'G' && $pendingActions->contains('responsible_role', 'provider')) {
            return 'lhxh_foreas';
        }

        if ($section === 'G' && $pendingActions->contains('responsible_role', 'scientific_supervisor')) {
            return 'lhxh_ey';
        }

        return null;
    }

    public function unavailableReason(Student $student, string $type): ?string
    {
        if (! isset(self::EMAILS[$type])) {
            return 'Άγνωστος τύπος email.';
        }

        return match (true) {
            $student->isCancelled() => 'Η πρακτική έχει ακυρωθεί.',
            $type === 'stoixeiaFoitEgkrish' && (! $student->actionIsComplete('stoix_student') || ! $student->actionIsComplete('stoix_forea')) => 'Απαιτείται πρώτα έγκριση στοιχείων.',
            $type === 'symf_SN_f_f_ey' && ! $student->hasApprovedDocument('symf_SN_f_f') => 'Δεν έχει εγκριθεί το συμφωνητικό με δύο υπογραφές.',
            in_array($type, ['symf_SN_FULL', 'erganh_enarxh'], true) && (! $student->hasApprovedDocument('symf_SN_FULL') || ! $student->hasApprovedDocument('ADA_diaugeia')) => 'Απαιτούνται τελικό συμφωνητικό και ΑΔΑ.',
            $type === 'input' && $student->fundingCode() !== 'Espa' => 'Το απογραφικό εισόδου αφορά μόνο το ΕΣΠΑ.',
            $type === 'input' && (! $student->startDate || $student->startDate->isFuture()) => 'Δεν έχει φτάσει η ημερομηνία έναρξης.',
            in_array($type, ['lhxh', 'lhxh_foreas'], true) && (! $student->endDate || $student->endDate->isFuture()) => 'Δεν έχει φτάσει η ημερομηνία λήξης.',
            $type === 'lhxh_ey' && (! $this->endActionsAreCompleteFor($student, 'student') || ! $this->endActionsAreCompleteFor($student, 'provider')) => 'Δεν έχουν ολοκληρωθεί οι ενέργειες λήξης φοιτητή και φορέα.',
            default => null,
        };
    }

    /** @return array<int, string> */
    private function recipientAddresses(Student $student, string $recipientRole): array
    {
        $emails = match ($recipientRole) {
            'student' => [$student->email],
            'provider' => [$student->provider?->f_email, $student->provider?->f_epopt_email],
            'supervisor' => [$student->supervisor?->email],
        };

        return collect($emails)->filter(fn ($email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)->unique()->values()->all();
    }

    private function endActionsAreCompleteFor(Student $student, string $role): bool
    {
        $actions = WorkflowAction::activeForFunding($student->fundingCode())
            ->where('section', 'G')
            ->where('responsible_role', $role)
            ->where('counts_for_section', true);

        return $actions->isNotEmpty()
            && $actions->every(fn (WorkflowAction $action): bool => $action->isCompleteFor($student));
    }
}
