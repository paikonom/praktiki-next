<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('praktiki_phases', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('legacy_fid');
            $table->foreignId('praktiki_cycle_id')->constrained('praktiki_cycles')->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name', 10)->nullable();
            $table->json('details')->nullable();
            $table->string('dates', 1000)->nullable();
            $table->text('notes')->nullable();
            $table->string('url_prosklhsh', 500)->nullable();
            $table->string('url_paratash', 500)->nullable();
            $table->string('url_prosorina', 500)->nullable();
            $table->string('url_enstaseis', 500)->nullable();
            $table->string('url_telika', 500)->nullable();
            $table->string('url_syneleysh', 500)->nullable();
            $table->timestamps();
            $table->unique(['praktiki_cycle_id', 'legacy_fid']);
            $table->unique(['department_id', 'praktiki_cycle_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('praktiki_phases');
    }
};
