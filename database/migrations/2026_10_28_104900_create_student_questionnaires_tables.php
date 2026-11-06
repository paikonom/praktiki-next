<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('version');
            $table->string('code', 20);
            $table->string('type', 20);
            $table->text('label');
            $table->json('options')->nullable();
            $table->unique(['version', 'code']);
        });

        Schema::create('student_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->unsignedTinyInteger('version');
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at');
            $table->timestamps();
        });

        Schema::create('student_questionnaire_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('student_questionnaires')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questionnaire_questions')->restrictOnDelete();
            $table->text('answer')->nullable();
            $table->unique(['questionnaire_id', 'question_id'], 'questionnaire_answer_unique');
        });

        $questions = require database_path('data/student_questionnaire_questions.php');

        DB::table('questionnaire_questions')->insert(array_map(
            fn (array $question): array => array_replace($question, [
                'options' => $question['options'] === null
                    ? null
                    : json_encode($question['options'], JSON_UNESCAPED_UNICODE),
            ]),
            $questions,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('student_questionnaire_answers');
        Schema::dropIfExists('student_questionnaires');
        Schema::dropIfExists('questionnaire_questions');
    }
};
