<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE student_questionnaires MODIFY submitted_at TIMESTAMP NULL');
        DB::statement('ALTER TABLE provider_questionnaires MODIFY submitted_at TIMESTAMP NULL');
    }

    public function down(): void
    {
        DB::table('student_questionnaires')->whereNull('submitted_at')->delete();
        DB::table('provider_questionnaires')->whereNull('submitted_at')->delete();
        DB::statement('ALTER TABLE student_questionnaires MODIFY submitted_at TIMESTAMP NOT NULL');
        DB::statement('ALTER TABLE provider_questionnaires MODIFY submitted_at TIMESTAMP NOT NULL');
    }
};
