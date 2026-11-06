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
        Schema::table('student_questionnaires', function (Blueprint $table) {
            $table->json('answers')->nullable()->after('version');
        });
        Schema::table('provider_questionnaires', function (Blueprint $table) {
            $table->json('answers')->nullable()->after('version');
        });

        DB::statement(<<<'SQL'
            UPDATE student_questionnaires AS questionnaire
            JOIN (
                SELECT answer.questionnaire_id,
                       JSON_OBJECTAGG(question.code, answer.answer) AS answers
                FROM student_questionnaire_answers AS answer
                JOIN questionnaire_questions AS question ON question.id = answer.question_id
                GROUP BY answer.questionnaire_id
            ) AS legacy_answers ON legacy_answers.questionnaire_id = questionnaire.id
            SET questionnaire.answers = legacy_answers.answers
        SQL);

        DB::statement(<<<'SQL'
            UPDATE provider_questionnaires AS questionnaire
            JOIN (
                SELECT answer.questionnaire_id,
                       JSON_OBJECTAGG(question.code, answer.answer) AS answers
                FROM provider_questionnaire_answers AS answer
                JOIN provider_questionnaire_questions AS question ON question.id = answer.question_id
                GROUP BY answer.questionnaire_id
            ) AS legacy_answers ON legacy_answers.questionnaire_id = questionnaire.id
            SET questionnaire.answers = legacy_answers.answers
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_questionnaires', fn (Blueprint $table) => $table->dropColumn('answers'));
        Schema::table('provider_questionnaires', fn (Blueprint $table) => $table->dropColumn('answers'));
    }
};
