<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scientific_supervisors', function (Blueprint $table) {
            $table->string('access_code', 20)->nullable()->unique()->after('email');
            $table->timestamp('last_login_at')->nullable()->after('access_code');
        });

        DB::table('scientific_supervisors')->orderBy('id')->each(function (object $supervisor): void {
            do {
                $code = Str::random(16);
            } while (DB::table('scientific_supervisors')->where('access_code', $code)->exists());

            DB::table('scientific_supervisors')->where('id', $supervisor->id)->update(['access_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('scientific_supervisors', function (Blueprint $table) {
            $table->dropUnique(['access_code']);
            $table->dropColumn(['access_code', 'last_login_at']);
        });
    }
};
