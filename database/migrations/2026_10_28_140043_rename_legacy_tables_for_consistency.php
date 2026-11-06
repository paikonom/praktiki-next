<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('foreas', 'student_providers');
        Schema::rename('questionnaire_questions', 'student_questionnaire_questions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('student_questionnaire_questions', 'questionnaire_questions');
        Schema::rename('student_providers', 'foreas');
    }
};
