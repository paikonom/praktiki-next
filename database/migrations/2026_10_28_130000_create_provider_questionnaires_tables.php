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
            $table->char('provider_access_token_hash', 64)->nullable()->unique()->after('kwdForea');
        });

        DB::statement("UPDATE students SET provider_access_token_hash = SHA2(TRIM(kwdForea), 256) WHERE NULLIF(TRIM(kwdForea), '') IS NOT NULL");

        Schema::create('provider_questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('version')->default(1);
            $table->string('code', 20);
            $table->string('type', 20);
            $table->text('label');
            $table->json('options')->nullable();
            $table->unique(['version', 'code']);
        });

        Schema::create('provider_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->unsignedTinyInteger('version')->default(1);
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at');
            $table->timestamps();
        });

        Schema::create('provider_questionnaire_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('provider_questionnaires')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('provider_questionnaire_questions')->restrictOnDelete();
            $table->text('answer')->nullable();
            $table->unique(['questionnaire_id', 'question_id'], 'provider_questionnaire_answer_unique');
        });

        $questions = require database_path('data/provider_questionnaire_questions.php');
        DB::table('provider_questionnaire_questions')->insert(array_map(
            fn (array $question): array => array_replace($question, [
                'options' => $question['options'] === null ? null : json_encode($question['options'], JSON_UNESCAPED_UNICODE),
            ]),
            $questions,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_questionnaire_answers');
        Schema::dropIfExists('provider_questionnaires');
        Schema::dropIfExists('provider_questionnaire_questions');
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['provider_access_token_hash']);
            $table->dropColumn('provider_access_token_hash');
        });
    }
};
