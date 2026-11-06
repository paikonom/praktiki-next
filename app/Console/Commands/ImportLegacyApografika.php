<?php

namespace App\Console\Commands;

use App\Models\StudentApografiko;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('legacy:import-apografika {--write : Persist the import; without this option only report what would happen}')]
#[Description('Import legacy input/output questionnaires without overwriting submissions made by the new application')]
class ImportLegacyApografika extends Command
{
    public function handle(): int
    {
        $stats = ['found' => 0, 'ready' => 0, 'protected' => 0, 'missing_student' => 0, 'invalid' => 0];
        $imports = [];
        $studentIds = DB::table('students')->pluck('id')->flip();

        foreach (['input' => ['apografikaIn', 'commentsIn'], 'output' => ['apografikaOut', 'commentsOut']] as $type => [$table, $commentsColumn]) {
            foreach (DB::table("praktiki_legacy_inventory.{$table}")->orderBy('sid')->cursor() as $row) {
                $stats['found']++;

                if (! $studentIds->has($row->sid)) {
                    $stats['missing_student']++;

                    continue;
                }

                $existing = StudentApografiko::query()->where('student_id', $row->sid)->where('type', $type)->first();
                if ($existing && (str_starts_with((string) $existing->request_payload, '<?xml') || str_starts_with((string) $existing->response_payload, '<?xml'))) {
                    $stats['protected']++;

                    continue;
                }

                $legacyAnswers = $this->decode($row->answers);
                $legacyResponse = $this->decode($row->resp);
                $legacyDetails = $this->decode($row->stoixeia);
                if (isset($legacyAnswers['_legacy_raw']) || isset($legacyResponse['_legacy_raw']) || isset($legacyDetails['_legacy_raw'])) {
                    $stats['invalid']++;
                }

                $answers = [];
                foreach ($legacyAnswers as $id => $answer) {
                    if (! is_numeric($id)) {
                        continue;
                    }
                    $answers[(int) $id] = match ($answer) {
                        'YES' => '5321',
                        'NO' => '5322',
                        default => $answer,
                    };
                }

                $deltioId = $this->deltioId($legacyResponse);
                $progressId = $this->value($row->longCode) ?: ($this->value($row->pendingCode) === '-' ? null : $this->value($row->pendingCode));
                $status = $existing?->status;
                if (! in_array($status, [StudentApografiko::STATUS_COMPLETED, StudentApografiko::STATUS_NOT_REQUIRED], true)) {
                    $status = $deltioId ? StudentApografiko::STATUS_COMPLETED : ($progressId ? StudentApografiko::STATUS_PROCESSING : StudentApografiko::STATUS_DRAFT);
                }

                $imports[] = [
                    'student_id' => (int) $row->sid,
                    'type' => $type,
                    'status' => $status,
                    'answers' => json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'request_progress_id' => $progressId,
                    'deltio_id' => $deltioId ?: $existing?->deltio_id,
                    'response_payload' => $legacyResponse === [] ? null : json_encode($legacyResponse, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'legacy_data' => json_encode([
                        'answers' => $legacyAnswers,
                        'stoixeia' => $legacyDetails,
                        'response' => $legacyResponse,
                        'comments' => $this->value($row->{$commentsColumn}),
                        'long_code' => $this->value($row->longCode),
                        'pending_code' => $this->value($row->pendingCode),
                    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'submitted_at' => $row->submitTime,
                    'completed_at' => $status === StudentApografiko::STATUS_COMPLETED ? $row->submitTime : null,
                    'created_at' => $existing?->created_at ?? $row->submitTime,
                    'updated_at' => now(),
                ];
                $stats['ready']++;
            }
        }

        $this->table(['Found', 'Ready', 'Protected new', 'Missing student', 'Invalid'], [array_values($stats)]);
        if (! $this->option('write')) {
            $this->info('Dry run only. Run again with --write to persist.');

            return $stats['invalid'] === 0 ? self::SUCCESS : self::FAILURE;
        }

        DB::transaction(function () use ($imports): void {
            foreach (array_chunk($imports, 500) as $chunk) {
                DB::table('student_apografika')->upsert(
                    $chunk,
                    ['student_id', 'type'],
                    ['status', 'answers', 'request_progress_id', 'deltio_id', 'response_payload', 'legacy_data', 'submitted_at', 'completed_at', 'updated_at'],
                );
            }
        });

        $this->info($stats['ready'].' legacy questionnaires imported.');

        return self::SUCCESS;
    }

    private function decode(?string $value): array
    {
        if ($this->value($value) === null) {
            return [];
        }
        $decoded = @unserialize($value, ['allowed_classes' => false]);

        return is_array($decoded) ? $decoded : ['_legacy_raw' => $value];
    }

    private function deltioId(array $response): ?int
    {
        $message = (string) ($response['errorDescr'] ?? '');

        return preg_match('/Α\/Α:\s*(\d+)/u', $message, $matches) ? (int) $matches[1] : null;
    }

    private function value(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
