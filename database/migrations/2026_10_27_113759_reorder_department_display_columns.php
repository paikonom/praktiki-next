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
        Schema::table('departments', function (Blueprint $table) {
            $table->string('tmhma', 100)->after('atlasId')->change();
            $table->string('polh', 50)->nullable()->after('tmhma')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('polh', 50)->nullable()->after('atlasId')->change();
            $table->string('tmhma', 100)->after('polh')->change();
        });
    }
};
