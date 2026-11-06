<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PraktikesSeeder extends Seeder
{
    public function run(): void
    {
        $fundingIds = DB::table('fundings')->pluck('id', 'code');
        $yearsByFunding = [
            'Espa' => ['2024-2025', '2025-2026', '2026-2027'],
            'Institution' => ['2024-2025', '2025-2026'],
            'InternshipProvider' => ['2024-2025', '2025-2026'],
        ];

        foreach ($yearsByFunding as $fundingCode => $years) {
            foreach ($years as $year) {
                DB::table('praktiki_cycles')->insertOrIgnore([
                    'funding_id' => $fundingIds[$fundingCode],
                    'praxh_year' => $year,
                    'details' => json_encode($this->details($fundingCode), JSON_UNESCAPED_UNICODE),
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /** @return array<string, string>|null */
    private function details(string $fundingCode): ?array
    {
        return match ($fundingCode) {
            'Espa' => [
                'ep_yp_ergou' => 'Χρήστος Καραγιάννης',
                'K_E_ergou' => '21769',
                'titlos_praxhs' => 'Πρακτική Άσκηση ΕΚΠΑ ακ. ετών 2025-2027',
                'titlos_programmatos' => 'Ανθρώπινο Δυναμικό και Κοινωνική Συνοχή 2021-2027',
                'titlos_ypoergou' => '«Πρακτική Άσκηση Ε.Κ.Π.Α ακ. ετών 2024-2025, 2025-2026 και 2026-2027»',
                'kwdikos_OPS' => '6020906',
                'scientific_supervisor_a_a_prosklhsh' => 'ΕΚΠ38',
                'scientific_supervisor_OPS_prosklhsh' => '11140',
                'kostos_apof_a_a' => '1552/2026',
                'kostos_apof_ADA' => 'ΕΙΦΡ46ΨΖ2Ν-6ΛΞ',
            ],
            'Institution' => [
                'ep_yp_ergou' => 'Χρήστος Καραγιάννης',
                'K_E_ergou' => '21977',
            ],
            default => null,
        };
    }
}
