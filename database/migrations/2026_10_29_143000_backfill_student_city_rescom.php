<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $normalize = static fn (string $value): string => preg_replace(
            '/\s+/u',
            ' ',
            mb_strtoupper(Str::ascii(trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')))),
        );

        $catalog = json_decode((string) file_get_contents(resource_path('data/cities.json')), true, 512, JSON_THROW_ON_ERROR);
        $mapping = collect($catalog['Table'])
            ->groupBy(fn (array $row): string => $normalize((string) $row['Περιγραφή']))
            ->filter(fn ($rows): bool => $rows->pluck('Κωδικός')->unique()->count() === 1)
            ->map(fn ($rows): int => (int) $rows->first()['Κωδικός'])
            ->all();

        $confirmed = DB::table('students')
            ->whereNotNull('address_odos_polh')
            ->where('address_odos_polh', '!=', '')
            ->whereNotNull('address_polh_rescom')
            ->select('address_odos_polh', 'address_polh_rescom')
            ->get()
            ->groupBy(fn (object $student): string => $normalize($student->address_odos_polh));

        foreach ($confirmed as $legacyName => $students) {
            $codes = $students->pluck('address_polh_rescom')->unique();

            if ($codes->count() === 1 && ! isset($mapping[$legacyName])) {
                $mapping[$legacyName] = (int) $codes->first();
            }
        }

        DB::table('students')
            ->whereNull('address_polh_rescom')
            ->whereNotNull('address_odos_polh')
            ->where('address_odos_polh', '!=', '')
            ->select('id', 'address_odos_polh')
            ->orderBy('id')
            ->chunkById(500, function ($students) use ($mapping, $normalize): void {
                foreach ($students as $student) {
                    $code = $mapping[$normalize($student->address_odos_polh)] ?? null;

                    if ($code !== null) {
                        DB::table('students')->where('id', $student->id)->update([
                            'address_polh_rescom' => $code,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Existing ResCom selections cannot be distinguished safely from backfilled values.
    }
};
