<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('document_types')->insertOrIgnore([
            'code' => 'symf_initial',
            'title' => 'Αρχικό συμφωνητικό - χωρίς υπογραφές',
            'instructions' => 'Δημοσιευμένη έκδοση του αρχικού συμφωνητικού που παραλαμβάνει ο φοιτητής.',
            'max_size_mb' => 15,
            'available_after' => 'always',
            'active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('document_types')->where('code', 'symf_initial')->update([
            'title' => 'Αρχικό συμφωνητικό - χωρίς υπογραφές',
            'instructions' => 'Δημοσιευμένη έκδοση του αρχικού συμφωνητικού που παραλαμβάνει ο φοιτητής.',
            'available_after' => 'always',
            'active' => true,
            'updated_at' => now(),
        ]);

        $documentTypeId = DB::table('document_types')->where('code', 'symf_initial')->value('id');

        foreach (['Espa', 'Institution', 'InternshipProvider'] as $fundingCode) {
            DB::table('document_type_fundings')->updateOrInsert([
                'document_type_id' => $documentTypeId,
                'funding_code' => $fundingCode,
            ]);
        }

        foreach (['student', 'provider', 'member'] as $role) {
            DB::table('document_type_roles')->updateOrInsert(
                ['document_type_id' => $documentTypeId, 'role' => $role],
                ['can_view' => $role !== 'provider', 'can_upload' => $role === 'member'],
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('document_types')->where('code', 'symf_initial')->delete();
    }
};
