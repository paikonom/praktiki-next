<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

#[Signature('legacy:import-users')]
#[Description('Import operators from the local legacy database')]
class ImportLegacyUsers extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (DB::table('users')->exists()) {
            $this->error('The destination users table is not empty. Nothing was imported.');

            return self::FAILURE;
        }

        $legacyUsers = DB::table('praktiki_legacy_inventory.users')
            ->orderBy('uid')
            ->get();

        if ($legacyUsers->isEmpty()) {
            $this->error('No legacy users were found.');

            return self::FAILURE;
        }

        $now = now();
        $users = $legacyUsers->map(function (object $legacyUser) use ($now): array {
            if (! password_get_info($legacyUser->password)['algo']) {
                throw new RuntimeException("Legacy user {$legacyUser->uid} has an unsupported password hash.");
            }

            return [
                'id' => $legacyUser->uid,
                'name' => $legacyUser->name,
                'username' => $legacyUser->username,
                'email' => $legacyUser->email,
                'telephone' => $legacyUser->tel,
                'password' => $legacyUser->password,
                'role' => $legacyUser->role,
                'view_only' => (bool) $legacyUser->viewOnly,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DB::transaction(fn () => DB::table('users')->insert($users));

        $this->info(count($users).' legacy users imported.');

        return self::SUCCESS;
    }
}
