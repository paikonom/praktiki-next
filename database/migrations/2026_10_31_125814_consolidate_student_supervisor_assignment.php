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
        Schema::table('scientific_supervisors', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('department_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['initial_supervisor_id']);
            $table->dropForeign(['current_supervisor_id']);
            $table->dropColumn('current_supervisor_id');
            $table->renameColumn('initial_supervisor_id', 'supervisor_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('supervisor_id')->references('id')->on('scientific_supervisors')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->renameColumn('supervisor_id', 'initial_supervisor_id');
            $table->unsignedBigInteger('current_supervisor_id')->nullable()->after('initial_supervisor_id');
        });

        DB::table('students')->update(['current_supervisor_id' => DB::raw('initial_supervisor_id')]);

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('initial_supervisor_id')->references('id')->on('scientific_supervisors')->restrictOnDelete();
            $table->foreign('current_supervisor_id')->references('id')->on('scientific_supervisors')->restrictOnDelete();
        });

        Schema::table('scientific_supervisors', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
