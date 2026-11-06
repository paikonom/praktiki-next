<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('praktiki_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_id')->constrained()->restrictOnDelete();
            $table->string('praxh_year', 20);
            $table->json('details')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['funding_id', 'praxh_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('praktiki_cycles');
    }
};
