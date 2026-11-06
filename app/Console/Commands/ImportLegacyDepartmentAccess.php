<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;

#[Signature('legacy:import-department-access')]
#[Description('Import user department access from the local legacy database')]
class ImportLegacyDepartmentAccess extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (DB::table('user_department_access')->exists()) {
            $this->error('The destination access table is not empty. Nothing was imported.');

            return self::FAILURE;
        }

        $departmentsByAtlasId = DB::table('departments')->pluck('id', 'atlasId');
        $now = now();
        $accessRows = [];

        foreach (DB::table('praktiki_legacy_inventory.users')->orderBy('uid')->get() as $legacyUser) {
            try {
                $atlasIds = json_decode($legacyUser->tmhmataAccess ?: '[]', true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException("Invalid tmhmataAccess for legacy user {$legacyUser->uid}.", previous: $exception);
            }

            if (! is_array($atlasIds)) {
                throw new RuntimeException("Invalid tmhmataAccess for legacy user {$legacyUser->uid}.");
            }

            foreach (array_unique($atlasIds) as $atlasId) {
                $departmentId = $departmentsByAtlasId->get($atlasId);

                if ($departmentId === null) {
                    throw new RuntimeException("Unknown atlasId {$atlasId} for legacy user {$legacyUser->uid}.");
                }

                $accessRows[] = [
                    'user_id' => $legacyUser->uid,
                    'department_id' => $departmentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::transaction(fn () => DB::table('user_department_access')->insert($accessRows));

        $this->info(count($accessRows).' user department access links imported.');

        return self::SUCCESS;
    }
}
