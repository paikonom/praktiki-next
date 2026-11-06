<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foreas', function (Blueprint $table) {
            $table->id('fid');
            $table->foreignId('sid')->unique()->constrained('students')->cascadeOnDelete();
            $table->string('f_epwnymia', 150)->nullable();
            $table->integer('f_nomikhMorfh_rescom')->nullable();
            $table->string('f_afm', 20)->nullable();
            $table->string('f_perioxh', 100)->nullable();
            $table->integer('f_polh_rescom')->nullable();
            $table->integer('f_xwra_rescom')->nullable();
            $table->string('f_odos', 100)->nullable();
            $table->string('f_tk', 10)->nullable();
            $table->integer('f_doy_rescom')->nullable();
            $table->integer('f_perifereia')->nullable();
            $table->string('f_thl', 50)->nullable();
            $table->string('f_email', 100)->nullable();
            $table->string('f_ekprFname', 200)->nullable();
            $table->string('f_ekprLname', 200)->nullable();
            $table->string('f_ekprIdiothta', 60)->nullable();
            $table->integer('dhm_idiwt_rescom')->nullable();
            $table->integer('f_proeleush_rescom')->nullable();
            $table->string('f_topos_ekt_prakt', 200)->nullable();
            $table->integer('f_ektPrakt_polh_rescom')->nullable();
            $table->string('f_ektPrakt_odos', 200)->nullable();
            $table->string('f_ektPrakt_tk', 10)->nullable();
            $table->string('f_epopt_foreaFname', 200)->nullable();
            $table->string('f_epopt_foreaLname', 200)->nullable();
            $table->string('f_epopt_email', 50)->nullable();
            $table->text('f_epopt_foreaThesh')->nullable();
            $table->text('f_epopt_foreaKatartish')->nullable();
            $table->text('f_antikeim_Praktikhs')->nullable();
            $table->text('f_apotel_Praktikhs')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foreas');
    }
};
