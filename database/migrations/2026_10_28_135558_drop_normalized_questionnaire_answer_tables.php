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
        Schema::drop('student_questionnaire_answers');
        Schema::drop('provider_questionnaire_answers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('student_questionnaire_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('student_questionnaires')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questionnaire_questions')->restrictOnDelete();
            $table->text('answer')->nullable();
            $table->unique(['questionnaire_id', 'question_id'], 'student_questionnaire_answer_unique');
        });
        Schema::create('provider_questionnaire_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('provider_questionnaires')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('provider_questionnaire_questions')->restrictOnDelete();
            $table->text('answer')->nullable();
            $table->unique(['questionnaire_id', 'question_id'], 'provider_questionnaire_answer_unique');
        });

        $this->restoreAnswers('student_questionnaires', 'questionnaire_questions', 'student_questionnaire_answers');
        $this->restoreAnswers('provider_questionnaires', 'provider_questionnaire_questions', 'provider_questionnaire_answers');
    }

    private function restoreAnswers(string $questionnairesTable, string $questionsTable, string $answersTable): void
    {
        $questionIds = DB::table($questionsTable)->pluck('id', 'code');

        DB::table($questionnairesTable)
            ->select(['id', 'answers'])
            ->orderBy('id')
            ->chunkById(500, function ($questionnaires) use ($questionIds, $answersTable): void {
                $rows = [];

                foreach ($questionnaires as $questionnaire) {
                    foreach (json_decode($questionnaire->answers ?? '[]', true) as $code => $answer) {
                        if ($questionIds->has($code)) {
                            $rows[] = [
                                'questionnaire_id' => $questionnaire->id,
                                'question_id' => $questionIds->get($code),
                                'answer' => $answer,
                            ];
                        }
                    }
                }

                if ($rows !== []) {
                    DB::table($answersTable)->insert($rows);
                }
            });
    }
};
