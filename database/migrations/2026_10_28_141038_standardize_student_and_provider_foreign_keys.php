<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_providers', function (Blueprint $table) {
            $table->dropForeign('foreas_sid_foreign');
            $table->dropUnique('foreas_sid_unique');
        });
        Schema::rename('student_providers', 'providers');
        Schema::table('providers', function (Blueprint $table) {
            $table->renameColumn('fid', 'id');
            $table->renameColumn('sid', 's_id');
        });
        Schema::table('providers', function (Blueprint $table) {
            $table->unique('s_id');
            $table->foreign('s_id')->references('id')->on('students')->cascadeOnDelete();
        });

        $this->renameStudentForeignKey('student_questionnaires');
        $this->renameStudentForeignKey('provider_questionnaires');

        Schema::table('provider_questionnaires', function (Blueprint $table) {
            $table->unsignedBigInteger('p_id')->nullable()->after('s_id');
        });
        DB::statement(<<<'SQL'
            UPDATE provider_questionnaires AS questionnaire
            JOIN providers AS provider ON provider.s_id = questionnaire.s_id
            SET questionnaire.p_id = provider.id
        SQL);
        DB::statement('ALTER TABLE provider_questionnaires MODIFY p_id BIGINT UNSIGNED NOT NULL');
        Schema::table('provider_questionnaires', function (Blueprint $table) {
            $table->unique('p_id');
            $table->foreign('p_id')->references('id')->on('providers')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_questionnaires', function (Blueprint $table) {
            $table->dropForeign(['p_id']);
            $table->dropUnique(['p_id']);
            $table->dropColumn('p_id');
        });

        $this->restoreStudentForeignKey('provider_questionnaires');
        $this->restoreStudentForeignKey('student_questionnaires');

        Schema::table('providers', function (Blueprint $table) {
            $table->dropForeign(['s_id']);
            $table->dropUnique(['s_id']);
            $table->renameColumn('id', 'fid');
            $table->renameColumn('s_id', 'sid');
        });
        Schema::rename('providers', 'student_providers');
        Schema::table('student_providers', function (Blueprint $table) {
            $table->unique('sid', 'foreas_sid_unique');
            $table->foreign('sid', 'foreas_sid_foreign')->references('id')->on('students')->cascadeOnDelete();
        });

    }

    private function renameStudentForeignKey(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $table->dropForeign($tableName.'_student_id_foreign');
            $table->dropUnique($tableName.'_student_id_unique');
            $table->renameColumn('student_id', 's_id');
        });
        Schema::table($tableName, function (Blueprint $table) {
            $table->unique('s_id');
            $table->foreign('s_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }

    private function restoreStudentForeignKey(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropForeign(['s_id']);
            $table->dropUnique(['s_id']);
            $table->renameColumn('s_id', 'student_id');
        });
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $table->unique('student_id');
            $table->foreign('student_id', $tableName.'_student_id_foreign')
                ->references('id')->on('students')->cascadeOnDelete();
        });
    }
};
