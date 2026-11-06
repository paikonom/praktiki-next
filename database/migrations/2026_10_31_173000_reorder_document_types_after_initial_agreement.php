<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_types')
            ->where('code', '!=', 'symf_initial')
            ->increment('sort_order');

        DB::table('document_types')
            ->where('code', 'symf_initial')
            ->update(['sort_order' => 0]);
    }

    public function down(): void
    {
        DB::table('document_types')
            ->where('code', '!=', 'symf_initial')
            ->where('sort_order', '>', 0)
            ->decrement('sort_order');
    }
};
