<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('fields')
            ->where('card_section', 'student')
            ->where('field', 'doy')
            ->delete();

        if (Schema::hasColumn('students', 'doy')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropColumn('doy');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'doy')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->string('doy', 100)->nullable()->after('placeDob');
            });
        }
    }
};
