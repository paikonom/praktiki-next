<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentQuestionnaire;
use App\Support\LegacyQuestionnaireAnswers;
use App\Support\StudentQuestionnaireDefinition;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('legacy:import-student-questionnaires {--write : Persist the import; without this option only report what would happen}')]
#[Description('Import legacy serialized student questionnaires into the normalized tables')]
class ImportLegacyStudentQuestionnaires extends Command
{
    public function handle(
        StudentQuestionnaireDefinition $definition,
        LegacyQuestionnaireAnswers $legacyAnswers,
    ): int {
        $legacyRows = DB::table('praktiki_legacy_final.questStudent')
            ->select(['sid', 'answers'])
            ->orderBy('sid')
            ->get();

        if ($legacyRows->isEmpty()) {
            $this->error('No legacy student questionnaires were found.');

            return self::FAILURE;
        }

        $students = Student::query()
            ->with(['department', 'questionnaire'])
            ->whereIn('id', $legacyRows->pluck('sid'))
            ->get()
            ->keyBy('id');
        $existingStudentIds = StudentQuestionnaire::query()->pluck('s_id')->flip();
        $questionSets = [];
        $imports = [];
        $stats = ['found' => $legacyRows->count(), 'ready' => 0, 'missing_student' => 0, 'existing' => 0, 'invalid' => 0];

        foreach ($legacyRows as $legacyRow) {
            $student = $students->get($legacyRow->sid);

            if (! $student) {
                $stats['missing_student']++;

                continue;
            }

            if ($existingStudentIds->has($student->id)) {
                $stats['existing']++;

                continue;
            }

            try {
                $decoded = $legacyAnswers->decode($legacyRow->answers);
            } catch (Throwable) {
                $stats['invalid']++;

                continue;
            }

            $version = $definition->version($student);
            $setKey = $version.':'.((int) $student->department?->atlasId === 171 ? '171' : 'default');
            $questions = $questionSets[$setKey] ??= $this->questions($definition, $student, $version);
            $answers = $questions
                ->filter(fn ($question) => array_key_exists($question->code, $decoded))
                ->mapWithKeys(fn ($question) => [
                    $question->code => $legacyAnswers->normalize($question, $decoded[$question->code]),
                ])->all();

            $imports[] = [
                's_id' => $student->id,
                'version' => $version,
                'status' => StudentQuestionnaire::STATUS_SUBMITTED,
                'submitted_at' => $this->submittedAt($decoded['complDate'] ?? null),
                'answers' => $answers,
            ];
            $stats['ready']++;
        }

        $this->table(
            ['Legacy records', 'Ready', 'Missing student', 'Already imported', 'Invalid serialized data'],
            [array_values($stats)],
        );

        if (! $this->option('write')) {
            $this->info('Dry run only. Run again with --write to persist these records.');

            return $stats['invalid'] === 0 ? self::SUCCESS : self::FAILURE;
        }

        DB::transaction(function () use ($imports): void {
            foreach ($imports as $import) {
                StudentQuestionnaire::create($import);
            }
        });

        $this->info($stats['ready'].' legacy student questionnaires imported.');

        return self::SUCCESS;
    }

    private function questions(
        StudentQuestionnaireDefinition $definition,
        Student $student,
        int $version,
    ): Collection {
        return collect($definition->sections($student, $version))
            ->pluck('questions')
            ->flatten();
    }

    private function submittedAt(mixed $value): Carbon
    {
        if (is_string($value)) {
            try {
                return Carbon::createFromFormat('d/m/Y h:i:s a', $value);
            } catch (Throwable) {
                // Fall through to a safe import timestamp.
            }
        }

        return now();
    }
}
