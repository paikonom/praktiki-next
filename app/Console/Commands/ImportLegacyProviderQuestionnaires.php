<?php

namespace App\Console\Commands;

use App\Models\ProviderQuestionnaire;
use App\Models\Student;
use App\Support\LegacyQuestionnaireAnswers;
use App\Support\ProviderQuestionnaireDefinition;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('legacy:import-provider-questionnaires {--write : Persist the import; without this option only report what would happen}')]
#[Description('Import legacy serialized provider questionnaires into the normalized tables')]
class ImportLegacyProviderQuestionnaires extends Command
{
    public function handle(ProviderQuestionnaireDefinition $definition, LegacyQuestionnaireAnswers $legacyAnswers): int
    {
        $legacyRows = DB::table('praktiki_legacy_final.questForeas')->select(['sid', 'answers'])->orderBy('sid')->get();
        $students = Student::query()->with(['department', 'provider', 'providerQuestionnaire'])->whereIn('id', $legacyRows->pluck('sid'))->get()->keyBy('id');
        $existing = ProviderQuestionnaire::query()->pluck('s_id')->flip();
        $imports = [];
        $questionSets = [];
        $stats = ['found' => $legacyRows->count(), 'ready' => 0, 'missing_student' => 0, 'existing' => 0, 'invalid' => 0];
        $missingStudentIds = [];
        $invalidStudentIds = [];

        foreach ($legacyRows as $legacyRow) {
            $student = $students->get($legacyRow->sid);
            if (! $student) {
                $stats['missing_student']++;
                $missingStudentIds[] = $legacyRow->sid;

                continue;
            }
            if ($existing->has($student->id)) {
                $stats['existing']++;

                continue;
            }

            try {
                $decoded = $legacyAnswers->decode($legacyRow->answers);
            } catch (Throwable) {
                $stats['invalid']++;
                $invalidStudentIds[] = $legacyRow->sid;

                continue;
            }

            $department = (int) $student->department?->atlasId;
            $setKey = in_array($department, [171, 309], true) ? (string) $department : 'default';
            $questions = $questionSets[$setKey] ??= collect($definition->sections($student))->pluck('questions')->flatten();
            $answers = $questions
                ->filter(fn ($question) => array_key_exists($question->code, $decoded))
                ->mapWithKeys(fn ($question) => [
                    $question->code => $legacyAnswers->normalize($question, $decoded[$question->code]),
                ])->all();

            $imports[] = [
                's_id' => $student->id,
                'p_id' => $student->provider->id,
                'version' => 1,
                'status' => ProviderQuestionnaire::STATUS_SUBMITTED,
                'submitted_at' => $this->submittedAt($decoded['complDate'] ?? null),
                'answers' => $answers,
            ];
            $stats['ready']++;
        }

        $this->table(
            ['Legacy records', 'Ready', 'Missing student', 'Already imported', 'Invalid serialized data'],
            [array_values($stats)],
        );
        if ($missingStudentIds !== []) {
            $this->warn('Missing student IDs: '.implode(', ', $missingStudentIds));
        }
        if ($invalidStudentIds !== []) {
            $this->warn('Invalid serialized answers for student IDs: '.implode(', ', $invalidStudentIds));
        }

        if (! $this->option('write')) {
            $this->info('Dry run only. Run again with --write to persist these records.');

            return $stats['invalid'] === 0 ? self::SUCCESS : self::FAILURE;
        }

        DB::transaction(function () use ($imports): void {
            foreach ($imports as $import) {
                ProviderQuestionnaire::create($import);
            }
        });

        $this->info($stats['ready'].' legacy provider questionnaires imported.');

        return self::SUCCESS;
    }

    private function submittedAt(mixed $value): Carbon
    {
        if (is_string($value)) {
            foreach (['m/d/Y h:i:s a', 'd/m/Y h:i:s a'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $value);
                } catch (Throwable) {
                }
            }
        }

        return now();
    }
}
