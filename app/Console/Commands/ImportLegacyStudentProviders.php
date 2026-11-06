<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('legacy:import-student-providers')]
#[Description('Import one provider row for every student from the legacy database and Atlas payloads')]
class ImportLegacyStudentProviders extends Command
{
    public function handle(): int
    {
        $legacyColumns = [
            'fid', 'sid', 'f_epwnymia', 'f_nomikhMorfh_rescom',
            'f_afm', 'f_perioxh', 'f_polh_rescom', 'f_xwra_rescom', 'f_odos',
            'f_tk', 'f_doy_rescom', 'f_perifereia', 'f_thl', 'f_email',
            'f_ekprFname', 'f_ekprLname', 'f_ekprIdiothta',
            'dhm_idiwt_rescom', 'f_prοeleush_rescom', 'f_topos_ekt_prakt',
            'f_epopt_foreaFname', 'f_epopt_foreaLname', 'f_epopt_email',
            'f_epopt_foreaThesh', 'f_epopt_foreaKatartish', 'f_antikeim_Praktikhs',
            'f_apotel_Praktikhs',
        ];

        $targetColumns = $legacyColumns;
        $targetColumns[0] = 'id';
        $targetColumns[1] = 's_id';
        $targetColumns[array_search('f_prοeleush_rescom', $targetColumns, true)] = 'f_proeleush_rescom';
        $columns = implode(', ', array_map(fn (string $column) => "`{$column}`", $targetColumns));
        $legacySelect = implode(', ', array_map(fn (string $column) => "`{$column}`", $legacyColumns));

        DB::statement(
            "INSERT IGNORE INTO `providers` ({$columns})
             SELECT {$legacySelect}
             FROM `praktiki_legacy_final`.`foreas`"
        );

        $copiedLegacyRows = DB::table('praktiki_legacy_final.foreas')->count();
        $atlasRows = 0;
        $emptyRows = 0;
        $invalidPayloads = 0;
        $repairedRows = 0;

        DB::table('students')
            ->leftJoin('providers', 'providers.s_id', '=', 'students.id')
            ->whereNull('providers.s_id')
            ->select('students.id', 'students.atlas_Provider')
            ->orderBy('students.id')
            ->chunkById(250, function ($students) use (&$atlasRows, &$emptyRows, &$invalidPayloads): void {
                foreach ($students as $student) {
                    $provider = $this->unserializeAtlasProvider($student->atlas_Provider);

                    if ($provider === null) {
                        $emptyRows++;

                        if (filled($student->atlas_Provider)) {
                            $invalidPayloads++;
                        }
                    } else {
                        $atlasRows++;
                    }

                    DB::table('providers')->insert([
                        's_id' => $student->id,
                        'f_epwnymia' => $provider['Name'] ?? null,
                        'f_afm' => $provider['AFM'] ?? null,
                        'f_thl' => $provider['ContactPhone'] ?? null,
                        'f_email' => $provider['ContactEmail'] ?? null,
                        'f_ekprFname' => $provider['ContactName'] ?? null,
                    ]);
                }
            }, 'students.id', 'id');

        DB::table('students')
            ->join('providers', 'providers.s_id', '=', 'students.id')
            ->join('praktiki_phases', 'praktiki_phases.id', '=', 'students.fash_id')
            ->join('praktiki_cycles', 'praktiki_cycles.id', '=', 'praktiki_phases.praktiki_cycle_id')
            ->whereNull('providers.f_epwnymia')
            ->whereNotNull('students.atlas_Provider')
            ->whereRaw('LENGTH(TRIM(students.atlas_Provider)) > 0')
            ->whereIn('praktiki_cycles.praxh_year', ['2022-2023', '2023-2024'])
            ->select('students.id', 'students.atlas_Provider')
            ->orderBy('students.id')
            ->each(function ($student) use (&$repairedRows): void {
                $provider = $this->unserializeAtlasProvider($student->atlas_Provider);

                if ($provider === null) {
                    return;
                }

                DB::table('providers')->where('s_id', $student->id)->update([
                    'f_epwnymia' => $provider['Name'] ?? null,
                    'f_afm' => $provider['AFM'] ?? null,
                    'f_thl' => $provider['ContactPhone'] ?? null,
                    'f_email' => $provider['ContactEmail'] ?? null,
                    'f_ekprFname' => $provider['ContactName'] ?? null,
                ]);

                $repairedRows++;
            });

        $this->info("{$copiedLegacyRows} existing provider rows copied.");
        $this->info("{$atlasRows} legacy students populated from Atlas; {$emptyRows} empty provider rows created.");

        if ($repairedRows > 0) {
            $this->info("{$repairedRows} existing provider rows repaired from Atlas.");
        }

        if ($invalidPayloads > 0) {
            $this->warn("{$invalidPayloads} non-empty Atlas payloads could not be read.");
        }

        return self::SUCCESS;
    }

    private function unserializeAtlasProvider(?string $payload): ?array
    {
        if (blank($payload)) {
            return null;
        }

        try {
            $provider = unserialize($payload, ['allowed_classes' => false]);
        } catch (Throwable) {
            $provider = false;
        }

        if (! is_array($provider)) {
            $repairedPayload = preg_replace_callback(
                '/s:\d+:"(.*?)";(?=s:\d+:|i:\d+;|N;|})/s',
                fn (array $match): string => 's:'.strlen($match[1]).':"'.$match[1].'";',
                $payload
            );

            try {
                $provider = unserialize($repairedPayload, ['allowed_classes' => false]);
            } catch (Throwable) {
                return null;
            }
        }

        return is_array($provider) ? $provider : null;
    }
}
