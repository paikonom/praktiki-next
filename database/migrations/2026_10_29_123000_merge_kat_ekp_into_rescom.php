<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('students', 'katEkp')) {
            $mapping = [
                'ΔΕ' => 2,
                'ΠΕ' => 3,
                'ΤΕ' => 4,
                'Δ.Ε.' => 2,
                'ΔΕΥΤΕΡΟΒΑΘΜΙΑ ΕΚΠΑΙΔΕΥΣΗ' => 2,
                'ΥΕ' => 1,
                'Π.Ε.' => 3,
            ];

            foreach ($mapping as $legacyValue => $rescomValue) {
                DB::table('students')
                    ->whereNull('katEkp_rescom')
                    ->where('katEkp', $legacyValue)
                    ->update(['katEkp_rescom' => $rescomValue]);
            }

            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('katEkp');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'katEkp')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('katEkp', 100)->nullable()->before('katEkp_rescom');
            });
        }
    }
};
