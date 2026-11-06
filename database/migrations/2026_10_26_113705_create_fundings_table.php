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
        Schema::create('fundings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->boolean('active')->default(true);
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });

        DB::table('fundings')->insert([
            ['code' => 'Espa', 'name' => 'ΕΣΠΑ', 'sort_order' => 1],
            ['code' => 'Institution', 'name' => 'Χωρίς χρηματοδότηση', 'sort_order' => 2],
            ['code' => 'InternshipProvider', 'name' => 'Με χρηματοδότηση φορέα υποδοχής', 'sort_order' => 3],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundings');
    }
};
