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
        Schema::table('user_department_access', function (Blueprint $table) {
            $table->boolean('can_manage_espa')->default(false);
            $table->boolean('can_manage_institution')->default(false);
            $table->boolean('can_manage_internship_provider')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_department_access', function (Blueprint $table) {
            $table->dropColumn([
                'can_manage_espa',
                'can_manage_institution',
                'can_manage_internship_provider',
            ]);
        });
    }
};
