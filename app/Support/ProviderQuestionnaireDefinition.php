<?php

namespace App\Support;

use App\Models\ProviderQuestionnaireQuestion;
use App\Models\Student;
use Illuminate\Support\Collection;

class ProviderQuestionnaireDefinition
{
    private const BASE_SECTIONS = [
        'report' => ['title' => 'Έκθεση επίδοσης ασκούμενου/ης', 'codes' => ['comm']],
        'A' => ['title' => 'Α. Ερωτήσεις για την πρακτική άσκηση γενικά', 'codes' => ['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A_comm']],
        'B' => ['title' => 'Β. Ερωτήσεις για τον/τη φοιτητή/τρια', 'codes' => ['B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8', 'B9', 'B10', 'B11', 'B12', 'B13', 'B14', 'B_comm']],
    ];

    /** @return array<string, array{title: string, questions: Collection<int, ProviderQuestionnaireQuestion>}> */
    public function sections(Student $student, int $version = 1): array
    {
        $sections = self::BASE_SECTIONS;
        $department = (int) $student->department?->atlasId;

        if ($department === 171) {
            $sections['G'] = ['title' => 'Γ. Γνώσεις ψυχολογίας', 'codes' => ['G1', 'G2', 'G3']];
            $sections['D'] = ['title' => 'Δ. Προσωπικές και διαπροσωπικές δεξιότητες', 'codes' => ['D1', 'D2', 'D3', 'D4', 'D5', 'D6', 'D7']];
        }

        if ($department === 309) {
            $sections['G'] = ['title' => 'Γ. Ερωτήσεις Τμήματος Οικονομικών', 'codes' => array_map(fn (int $number): string => 'G'.$number.'_oik', range(1, 10))];
        }

        $questions = ProviderQuestionnaireQuestion::query()
            ->where('version', $version)
            ->whereIn('code', collect($sections)->pluck('codes')->flatten())
            ->get()
            ->keyBy('code');

        return collect($sections)->map(fn (array $section): array => [
            'title' => $section['title'],
            'questions' => collect($section['codes'])->map(fn (string $code) => $questions->get($code))->filter()->values(),
        ])->all();
    }

    public function unavailableReason(Student $student): ?string
    {
        return app(WorkflowDisplay::class)->unavailableReason($student, 'ekthesh_forea_sumpl', 'provider');
    }
}
