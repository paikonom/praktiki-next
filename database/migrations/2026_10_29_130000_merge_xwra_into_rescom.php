<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('students', 'xwra')) {
            DB::table('students')
                ->whereNull('xwra_rescom')
                ->whereNotNull('xwra')
                ->where('xwra', '!=', '')
                ->update([
                    'xwra_rescom' => DB::raw("CASE WHEN TRIM(xwra) = 'ΚΥΠΡΟΣ' THEN 196 ELSE 300 END"),
                ]);

            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('xwra');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'xwra')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('xwra', 100)->nullable()->before('xwra_rescom');
            });
        }
    }
};
