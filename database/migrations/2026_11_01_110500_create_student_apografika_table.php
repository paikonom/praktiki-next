<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_apografika', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('status', 20)->default('draft');
            $table->longText('answers')->nullable();
            $table->string('request_progress_id')->nullable();
            $table->unsignedBigInteger('deltio_id')->nullable();
            $table->longText('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->json('legacy_data')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'type']);
            $table->index(['status', 'type']);
        });

        Schema::create('espaQuestions_new', function (Blueprint $table): void {
            $table->integer('id_microdata')->primary();
            $table->string('kodikos_erotimatos')->nullable();
            $table->string('perigrafi', 1000)->nullable();
            $table->integer('id_patera')->nullable();
            $table->integer('epipedo')->nullable();
            $table->text('epexhghsh')->nullable();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('espaQuestions_new');
        Schema::dropIfExists('student_apografika');
    }
};
