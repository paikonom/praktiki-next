<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('atlasId')->unique();
            $table->string('polh', 50)->nullable();
            $table->string('tmhma', 100);
            $table->string('sxolh', 100)->nullable();
            $table->string('proedros', 100)->nullable();
            $table->string('proedros_vathm', 100)->nullable();
            $table->string('FY_arith_pryt_praxh', 100)->nullable();
            $table->string('FY_ADA_pryt_praxh', 100)->nullable();
            $table->string('FY_FEK_pryt_praxh', 100)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('scientific_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('fName', 100);
            $table->string('lName', 100);
            $table->string('email', 100);
            $table->string('thl', 50)->nullable();
            $table->string('apofash_tmhmatos', 100)->nullable();
            $table->string('syneleush_tmhmatos', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scientific_supervisors');
        Schema::dropIfExists('departments');
    }
};
