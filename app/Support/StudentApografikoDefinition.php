<?php

namespace App\Support;

use App\Models\EspaQuestion;
use App\Models\Student;
use App\Models\StudentApografiko;
use Illuminate\Support\Collection;

class StudentApografikoDefinition
{
    public function unavailableReason(Student $student, string $type): ?string
    {
        $student->loadMissing(['phase.praktiki.funding', 'documents.type']);

        if ($student->fundingCode() !== 'Espa') {
            return 'Απογραφικά δελτία απαιτούνται μόνο για πρακτική ΕΣΠΑ.';
        }

        if ($type === 'input') {
            if (! $student->studentBeforeStartComplete()) {
                return 'Πρέπει πρώτα να ολοκληρωθεί η ενότητα Α.';
            }

            if (! $student->startDate || now()->lt($student->startDate)) {
                return 'Δεν έχει φτάσει ακόμη η ημερομηνία έναρξης της πρακτικής σας.';
            }

            return null;
        }

        if (! $student->studentDuringComplete()) {
            return 'Πρέπει πρώτα να ολοκληρωθεί η ενότητα Β.';
        }

        if (! $student->endDate || now()->lt($student->endDate)) {
            return 'Δεν έχει φτάσει ακόμη η ημερομηνία λήξης της πρακτικής σας.';
        }

        return null;
    }

    /** @return array<string, string|null> */
    public function formAnswers(?StudentApografiko $apografiko): array
    {
        $answers = $apografiko?->answers ?? [];
        $education = $this->educationQuestions()
            ->keys()
            ->first(fn (int $id): bool => (string) ($answers[$id] ?? '') === config('praktiki_apografika.answers.yes'));

        return [
            'education_level' => $education ? (string) $education : null,
            'indicators' => $this->indicatorQuestions()
                ->keys()
                ->mapWithKeys(fn (int $id): array => [(string) $id => $answers[$id] ?? null])
                ->all(),
        ];
    }

    /** @param array<string, mixed> $submittedAnswers
     * @return array<int, string|null>
     */
    public function normalizedAnswers(string $type, array $submittedAnswers): array
    {
        $answers = config("praktiki_apografika.{$type}.fixed_answers", []);

        if ($type === 'output') {
            return $answers;
        }

        $selectedEducation = $submittedAnswers['education_level'] ?? null;
        foreach ($this->educationQuestions()->keys() as $id) {
            $answers[$id] = $selectedEducation === null
                ? null
                : ((string) $id === (string) $selectedEducation
                    ? config('praktiki_apografika.answers.yes')
                    : config('praktiki_apografika.answers.no'));
        }

        foreach ($this->indicatorQuestions()->keys() as $id) {
            $answers[$id] = $submittedAnswers['indicators'][(string) $id] ?? null;
        }

        return $answers;
    }

    /** @return Collection<int, EspaQuestion> */
    public function educationQuestions(): Collection
    {
        return $this->questions([27, 28, 29, 99, 100, 101, 32, 33, 34]);
    }

    /** @return Collection<int, EspaQuestion> */
    public function indicatorQuestions(): Collection
    {
        return $this->questions([117, 102, 112, 113, 114, 115, 38, 39, 40, 49]);
    }

    /** @return Collection<int, EspaQuestion> */
    public function inputTrainingQuestions(): Collection
    {
        return $this->questions([57, 22, 98, 25, 26, 116]);
    }

    /** @return Collection<int, EspaQuestion> */
    public function outputQuestions(): Collection
    {
        return $this->questions([111, 22, 98, 25, 26, 116]);
    }

    /** @return Collection<int, EspaQuestion> */
    public function answeredQuestions(StudentApografiko $apografiko): Collection
    {
        $ids = array_map('intval', array_keys($apografiko->answers ?? []));
        $questions = EspaQuestion::query()->whereIn('id_microdata', $ids)->get()->keyBy('id_microdata');

        return collect($ids)->mapWithKeys(fn (int $id): array => $questions->has($id) ? [$id => $questions->get($id)] : []);
    }

    /** @param list<int> $ids
     * @return Collection<int, EspaQuestion>
     */
    private function questions(array $ids): Collection
    {
        $questions = EspaQuestion::query()->whereIn('id_microdata', $ids)->get()->keyBy('id_microdata');

        return collect($ids)->mapWithKeys(function (int $id) use ($questions): array {
            $question = $questions->get($id);
            throw_unless($question, new \RuntimeException("Missing ESPA question {$id}."));

            return [$id => $question];
        });
    }
}
