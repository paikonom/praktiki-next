<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $documentTypeId = DB::table('document_types')->where('code', 'dhl_fys_pros')->value('id');

        if ($documentTypeId) {
            DB::table('document_type_fundings')->insertOrIgnore([
                'document_type_id' => $documentTypeId,
                'funding_code' => 'InternshipProvider',
            ]);
        }
    }

    public function down(): void
    {
        $documentTypeId = DB::table('document_types')->where('code', 'dhl_fys_pros')->value('id');

        if ($documentTypeId) {
            DB::table('document_type_fundings')
                ->where('document_type_id', $documentTypeId)
                ->where('funding_code', 'InternshipProvider')
                ->delete();
        }
    }
};
