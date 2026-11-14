<?php

namespace App\Support;

use App\Models\QuestionnaireQuestion;
use App\Models\Student;
use Illuminate\Support\Collection;

class StudentQuestionnaireDefinition
{
    private const V1_SECTIONS = [
        'report' => ['title' => 'Αναλυτική Έκθεση Πεπραγμένων', 'codes' => ['comm']],
        'A' => ['title' => 'Α. Γενικές ερωτήσεις για την πρακτική άσκηση', 'codes' => ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A_comm']],
        'B' => ['title' => 'Β. Ερωτήσεις για τον φορέα απασχόλησης', 'codes' => ['B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B_comm']],
        'G' => ['title' => 'Γ. Ερωτήσεις για τους/τις επόπτες/τριες του Τμήματος', 'codes' => ['G1', 'G2', 'G3', 'G4', 'G5', 'G6', 'G_comm1', 'G_comm2']],
        'D' => ['title' => 'Δ. Ερωτήσεις για τη διαδικασία της Πρακτικής Άσκησης', 'codes' => ['D1', 'D2', 'D3', 'D4', 'D_comm1']],
    ];

    private const V2_SECTIONS = [
        'report' => ['title' => 'Β. Αναλυτική Έκθεση Πεπραγμένων', 'codes' => ['comm']],
        'G' => ['title' => 'Γ. Οργάνωση και Διαδικασία Πρακτικής Άσκησης (ΕΚΠΑ)', 'codes' => ['G1', 'G2', 'G3', 'G4', 'G5']],
        'D' => ['title' => 'Δ. Φορέας Απασχόλησης', 'codes' => ['D1', 'D2', 'D3', 'D4', 'D5']],
        'E' => ['title' => 'Ε. Υπεύθυνος/η Πρακτικής Άσκησης – Επόπτης/τρια Εκπαιδευτικός', 'codes' => ['E1', 'E2', 'E3', 'E4', 'E5']],
        'ST' => ['title' => 'ΣΤ. Εκπαιδευτική Αξία και Αποκτηθείσες Δεξιότητες', 'codes' => ['ST1', 'ST2', 'ST3', 'ST4', 'ST5']],
    ];

    public function version(Student $student): int
    {
        return (int) $student->questionnaire_version;
    }

    /** @return array<string, array{title: string, questions: Collection<int, QuestionnaireQuestion>}> */
    public function sections(Student $student, ?int $version = null): array
    {
        $version ??= $this->version($student);
        $sections = $version === 2 ? self::V2_SECTIONS : self::V1_SECTIONS;

        if ($version === 1 && (int) $student->department?->atlasId === 171) {
            $sections['B']['codes'][0] = 'B1_psyx';
            $sections['B']['codes'][4] = 'B5_psyx';
        }

        $codes = collect($sections)->pluck('codes')->flatten()->all();
        $questions = QuestionnaireQuestion::query()
            ->where('version', $version)
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');

        return collect($sections)->map(fn (array $section): array => [
            'title' => $section['title'],
            'questions' => collect($section['codes'])->map(fn (string $code) => $questions->get($code))->filter()->values(),
        ])->all();
    }

    public function unavailableReason(Student $student): ?string
    {
        return app(WorkflowDisplay::class)->unavailableReason($student, 'ekthesh_foit_sumpl', 'student');
    }
}
