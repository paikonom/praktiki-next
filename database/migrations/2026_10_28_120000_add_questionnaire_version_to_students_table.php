<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedTinyInteger('questionnaire_version')->nullable()->after('fash_id');
        });

        DB::table('students')->update([
            'questionnaire_version' => DB::raw("CASE WHEN startDate > '2025-12-31' THEN 2 ELSE 1 END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('questionnaire_version');
        });
    }
};
