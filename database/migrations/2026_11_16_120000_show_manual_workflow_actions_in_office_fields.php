<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('fields')->insertOrIgnore([
            [
                'card_section' => 'office',
                'field' => 'aitima_plhrwmhs',
                'fundings' => json_encode(['all']),
                'visible_to_roles' => json_encode(['member']),
                'label' => 'ΚΑΤΑΣΤΑΣΗ ΑΙΤΗΜΑΤΟΣ ΠΛΗΡΩΜΗΣ',
                'help_text' => null,
                'section' => 'Κατάσταση διαδικασίας',
                'sort_order' => 3,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'card_section' => 'office',
                'field' => 'katastash_praktikis',
                'fundings' => json_encode(['all']),
                'visible_to_roles' => json_encode(['member']),
                'label' => 'ΚΑΤΑΣΤΑΣΗ ΠΡΑΚΤΙΚΗΣ ΑΣΚΗΣΗΣ',
                'help_text' => null,
                'section' => 'Κατάσταση διαδικασίας',
                'sort_order' => 4,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('fields')
            ->where('card_section', 'office')
            ->whereIn('field', ['aitima_plhrwmhs', 'katastash_praktikis'])
            ->delete();
    }
};
