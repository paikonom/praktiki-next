<?php

namespace App\Support;

use App\Models\Student;
use Carbon\Carbon;

class VisibleInternshipDetails
{
    public function __construct(private StudentCardMetadata $fieldVisibility) {}

    /** @return array<int, array{key: string, title: string, details: array<int, array{label: string, value: mixed, missing: bool}>}> */
    public function forRole(Student $student, RescomReferenceData $rescomReferenceData, string $viewerRole = 'student'): array
    {
        $student->loadMissing(['department', 'phase.praktiki.funding', 'provider', 'supervisor']);
        $phase = $student->phase;
        $cycle = $phase?->praktiki;
        $fundingCode = $student->fundingCode();

        $cycleValues = ['funding' => $cycle?->funding_name, 'praxh_year' => $cycle?->praxh_year]
            + ($cycle?->details ?? []);
        $cycleFields = ['funding' => 'Χρηματοδότηση', 'praxh_year' => 'Έτος πράξης']
            + $this->definitionLabels(config("praktiki_internship.cycle_fields.{$fundingCode}", []));

        $phaseValues = ['name' => $phase?->name, 'dates' => $phase?->dates]
            + ($phase?->details ?? []);
        $phaseFields = ['name' => 'Φάση', 'dates' => 'Περίοδος / οδηγίες ημερομηνιών']
            + $this->definitionLabels(config("praktiki_internship.phase_fields.{$fundingCode}", []));
        foreach (config('praktiki_internship.phase_urls') as $field => $definition) {
            if (filled($phase?->{$field})) {
                $phaseFields[$field] = str_replace('URL ', '', $definition['label']);
                $phaseValues[$field] = $phase->{$field};
            }
        }

        $department = $student->department;
        $supervisor = $student->supervisor;
        $departmentFields = [
            'department' => 'Τμήμα',
            'school' => 'Σχολή',
            'scientific_supervisor' => 'Επιστημονικός Υπεύθυνος',
            'scientific_supervisor_email' => 'Email Επιστημονικού Υπεύθυνου',
            'scientific_supervisor_phone' => 'Τηλέφωνο Επιστημονικού Υπεύθυνου',
            'academic_supervisor' => 'Επόπτης καθηγητής',
        ];
        $departmentValues = [
            'department' => $department?->tmhma,
            'school' => $department?->sxolh,
            'scientific_supervisor' => $supervisor?->full_name,
            'scientific_supervisor_email' => $supervisor?->email,
            'scientific_supervisor_phone' => $supervisor?->thl,
            'academic_supervisor' => $student->epopt_kath,
        ];

        $visibleStudentFields = $this->visibleFieldLabels('student', $fundingCode, $viewerRole);

        return [
            ['key' => 'internship', 'title' => 'Γενικά στοιχεία πρακτικής', 'details' => $this->details($cycleFields, $cycleValues, $rescomReferenceData)],
            ['key' => 'phase', 'title' => 'Στοιχεία φάσης', 'details' => $this->details($phaseFields, $phaseValues, $rescomReferenceData)],
            ['key' => 'department', 'title' => 'Στοιχεία τμήματος', 'details' => $this->details($departmentFields, $departmentValues, $rescomReferenceData)],
            ['key' => 'student', 'title' => 'Στοιχεία φοιτητή', 'details' => $this->details($visibleStudentFields, $student->getAttributes(), $rescomReferenceData)],
            ['key' => 'provider', 'title' => 'Στοιχεία φορέα', 'details' => $this->details(
                $this->visibleFieldLabels('provider', $fundingCode, $viewerRole),
                $student->provider?->getAttributes() ?? [],
                $rescomReferenceData,
            )],
        ];
    }

    /** @return array<string, string> */
    private function visibleFieldLabels(string $cardSection, string $funding, string $viewerRole): array
    {
        // The fields table is the single place that decides what each role may see.
        return $this->fieldVisibility->labels($this->fieldVisibility->fields(
            $cardSection, $funding, $viewerRole,
        ));
    }

    private function definitionLabels(array $definitions): array
    {
        return collect($definitions)->map(fn (array $definition): string => $definition['label'])->all();
    }

    /** @return array<int, array{label: string, value: mixed, missing: bool}> */
    private function details(array $fieldLabels, array $storedValues, RescomReferenceData $rescomReferenceData): array
    {
        return collect($fieldLabels)->map(function (string $label, string $field) use ($storedValues, $rescomReferenceData): array {
            $value = $storedValues[$field] ?? null;
            $missing = $value === null || $value === '';

            if (! $missing && isset(config('praktiki_fields.catalog_fields')[$field])) {
                $value = $rescomReferenceData->label(config('praktiki_fields.catalog_fields')[$field], $value) ?? $value;
            } elseif (! $missing && isset(config('praktiki_fields.select_options')[$field])) {
                $value = config('praktiki_fields.select_options.'.$field.'.'.$value, $value);
            } elseif (! $missing && config('praktiki_fields.field_types.'.$field) === 'date') {
                $value = Carbon::parse($value)->format('d/m/Y');
            }

            return ['label' => $label, 'value' => $missing ? '—' : $value, 'missing' => $missing];
        })->values()->all();
    }
}
