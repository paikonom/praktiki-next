<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('workflow_actions')->insertOrIgnore([
            'funding_code' => 'all',
            'section' => 'G',
            'sort_order' => 8.10,
            'code' => 'vevaiwsh_EY',
            'title' => 'Ανέβασμα Βεβαίωσης Ολοκλήρωσης Επιστημονικού Υπευθύνου Τμήματος',
            'responsible_role' => 'scientific_supervisor',
            'available_after' => 'internship_end',
            'prerequisites' => json_encode([]),
            'source_type' => 'document',
            'source_key' => 'vevaiwsh_EY',
            'allowed_statuses' => json_encode(['submitted', 'approved', 'rejected', 'not_required']),
            'completed_statuses' => json_encode(['approved', 'not_required']),
            'counts_for_section' => true,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('document_types', function (Blueprint $table): void {
            $table->dropColumn('available_after');
        });

        Schema::table('workflow_actions', function (Blueprint $table): void {
            $table->dropColumn('available_after');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table): void {
            $table->string('available_after', 30)->default('always')->after('max_size_mb');
        });

        Schema::table('workflow_actions', function (Blueprint $table): void {
            $table->string('available_after', 30)->default('always')->after('responsible_role');
        });

        DB::table('workflow_actions')->where('section', 'B')->update(['available_after' => 'internship_start']);
        DB::table('workflow_actions')->whereIn('section', ['G', 'D'])->update(['available_after' => 'internship_end']);
        DB::table('workflow_actions')->where('code', 'katastash_praktikis')->update(['available_after' => 'always']);
        DB::table('document_types')
            ->whereIn('code', DB::table('workflow_actions')->where('source_type', 'document')->whereIn('section', ['G', 'D'])->select('source_key'))
            ->update(['available_after' => 'internship_end']);
    }
};
