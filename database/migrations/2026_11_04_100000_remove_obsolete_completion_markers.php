<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACTION_CODES = ['all_files_foit', 'all_files_foreas', 'all_files_scientific_supervisor'];

    public function up(): void
    {
        DB::table('workflow_actions')->whereIn('code', self::ACTION_CODES)->delete();
        DB::table('student_actions')->whereIn('action', self::ACTION_CODES)->delete();

        Schema::table('workflow_section_rules', function (Blueprint $table): void {
            $table->dropColumn('completion_actions');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_section_rules', function (Blueprint $table): void {
            $table->json('completion_actions')->nullable()->after('available_after');
        });
    }
};
