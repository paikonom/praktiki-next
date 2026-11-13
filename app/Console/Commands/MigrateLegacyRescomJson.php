<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('rescom:migrate-legacy-json {--write : Save the converted values}')]
#[Description('Convert OLD_rescom_resp values to the rescom_resp JSON column')]
class MigrateLegacyRescomJson extends Command
{
    public function handle(): int
    {
        $write = (bool) $this->option('write');
        $counts = ['converted' => 0, 'empty' => 0, 'invalid' => 0];

        DB::table('students')
            ->select('id', 'OLD_rescom_resp')
            ->orderBy('id')
            ->chunkById(250, function ($students) use ($write, &$counts): void {
                foreach ($students as $student) {
                    $value = $this->decode($student->OLD_rescom_resp);

                    if (blank($student->OLD_rescom_resp)) {
                        $counts['empty']++;
                    } elseif ($value === null) {
                        $counts['invalid']++;

                        continue;
                    } else {
                        $counts['converted']++;
                    }

                    if ($write) {
                        DB::table('students')->where('id', $student->id)->update([
                            'rescom_resp' => $value === null
                                ? null
                                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                        ]);
                    }
                }
            });

        $this->table(['Converted', 'Empty -> NULL', 'Invalid'], [[
            $counts['converted'], $counts['empty'], $counts['invalid'],
        ]]);
        $this->info($write ? 'Η μεταφορά ολοκληρώθηκε.' : 'Dry-run μόνο. Για αποθήκευση: php artisan rescom:migrate-legacy-json --write');

        return $counts['invalid'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function decode(?string $value): mixed
    {
        if (blank($value)) {
            return null;
        }

        if (! str_starts_with($value, 'a:')) {
            return $value;
        }

        $decoded = @unserialize($value, ['allowed_classes' => false]);

        return is_array($decoded) ? $decoded : null;
    }
}
