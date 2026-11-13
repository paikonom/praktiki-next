<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('atlas:migrate-legacy-json {--write : Save the converted JSON values} {--repair-encoding : Repair mojibake in existing JSON values}')]
#[Description('Convert the legacy serialized ATLAS payloads to the new JSON columns')]
class MigrateLegacyAtlasJson extends Command
{
    public function handle(): int
    {
        $write = (bool) $this->option('write');
        $repairEncoding = (bool) $this->option('repair-encoding');
        $counts = [
            'assignment_converted' => 0,
            'assignment_empty' => 0,
            'assignment_invalid' => 0,
            'assignment_existing' => 0,
            'provider_converted' => 0,
            'provider_empty' => 0,
            'provider_invalid' => 0,
            'provider_existing' => 0,
            'encoding_repaired' => 0,
        ];

        DB::table('students')
            ->select([
                'id', 'OLD_atlas_Antistoixish', 'OLD_atlas_Provider', 'atlas_Antistoixish', 'atlas_Provider',
                DB::raw('JSON_TYPE(atlas_Antistoixish) AS assignment_json_type'),
                DB::raw('JSON_TYPE(atlas_Provider) AS provider_json_type'),
            ])
            ->orderBy('id')
            ->chunkById(250, function ($students) use ($write, $repairEncoding, &$counts): void {
                foreach ($students as $student) {
                    $updates = [];

                    if ($repairEncoding) {
                        foreach (['atlas_Antistoixish', 'atlas_Provider'] as $column) {
                            $value = json_decode($student->{$column}, true);
                            $repaired = $this->repairEncoding($value);

                            if ($repaired !== $value) {
                                $updates[$column] = json_encode($repaired, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                                $counts['encoding_repaired']++;
                            }
                        }
                    }

                    $this->convertColumn(
                        $student->OLD_atlas_Antistoixish,
                        $student->assignment_json_type,
                        'atlas_Antistoixish',
                        'assignment',
                        $updates,
                        $counts,
                    );
                    $this->convertColumn(
                        $student->OLD_atlas_Provider,
                        $student->provider_json_type,
                        'atlas_Provider',
                        'provider',
                        $updates,
                        $counts,
                    );

                    if ($write && $updates !== []) {
                        DB::table('students')->where('id', $student->id)->update($updates);
                    }
                }
            });

        $this->table(['Payload', 'Converted', 'Empty', 'Invalid', 'Already JSON'], [
            ['Assignment', $counts['assignment_converted'], $counts['assignment_empty'], $counts['assignment_invalid'], $counts['assignment_existing']],
            ['Provider', $counts['provider_converted'], $counts['provider_empty'], $counts['provider_invalid'], $counts['provider_existing']],
        ]);
        $this->info($write ? 'Οι έγκυρες τιμές αποθηκεύτηκαν.' : 'Dry-run μόνο. Για αποθήκευση: php artisan atlas:migrate-legacy-json --write');
        $this->line('Τιμές JSON με διορθωμένη κωδικοποίηση: '.$counts['encoding_repaired']);

        return ($counts['assignment_invalid'] + $counts['provider_invalid']) > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /** @param array<string, string> $updates @param array<string, int> $counts */
    private function convertColumn(
        ?string $legacyValue,
        ?string $jsonType,
        string $targetColumn,
        string $counterPrefix,
        array &$updates,
        array &$counts,
    ): void {
        if ($jsonType !== null && $jsonType !== 'NULL') {
            $counts[$counterPrefix.'_existing']++;

            return;
        }

        if (blank($legacyValue)) {
            $counts[$counterPrefix.'_empty']++;

            return;
        }

        $value = $this->decodeLegacyPayload($legacyValue);

        if ($value === null) {
            $counts[$counterPrefix.'_invalid']++;

            return;
        }

        $updates[$targetColumn] = json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        $counts[$counterPrefix.'_converted']++;
    }

    private function decodeLegacyPayload(string $payload): ?array
    {
        $json = json_decode($payload, true);

        if (is_array($json)) {
            return $json;
        }

        try {
            $value = unserialize($payload, ['allowed_classes' => false]);
        } catch (Throwable) {
            $value = false;
        }

        if (! is_array($value)) {
            $repairedPayload = preg_replace_callback(
                '/s:\d+:"(.*?)";(?=s:\d+:|i:-?\d+;|d:[^;]+;|b:[01];|a:\d+:{|N;|})/s',
                fn (array $match): string => 's:'.strlen($match[1]).':"'.$match[1].'";',
                $payload,
            );

            try {
                $value = unserialize($repairedPayload, ['allowed_classes' => false]);
            } catch (Throwable) {
                return null;
            }
        }

        return is_array($value) ? $value : null;
    }

    private function repairEncoding(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->repairEncoding($item), $value);
        }

        if (! is_string($value) || ! preg_match('/[\x{0080}-\x{009F}]|(?:Î|Ï|Ã|â|ð)/u', $value)) {
            return $value;
        }

        $repaired = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');

        return mb_check_encoding($repaired, 'UTF-8') ? $repaired : $value;
    }
}
