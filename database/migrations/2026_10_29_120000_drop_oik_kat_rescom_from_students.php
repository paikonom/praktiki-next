<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('students', 'oikKat_rescom')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('oikKat_rescom');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'oikKat_rescom')) {
            Schema::table('students', function (Blueprint $table) {
                $table->integer('oikKat_rescom')->nullable()->after('oikKat');
            });
        }
    }
};
