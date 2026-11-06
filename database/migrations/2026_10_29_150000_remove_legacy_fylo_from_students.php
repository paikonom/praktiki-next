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
            ->where('field', 'fylo')
            ->delete();

        DB::table('fields')->updateOrInsert(
            ['card_section' => 'student', 'field' => 'fylo_rescom'],
            [
                'label' => 'ΦΥΛΟ',
                'fundings' => json_encode(['all']),
                'visible_to_roles' => json_encode(['member', 'student']),
                'help_text' => 'Επιλέξτε φύλο.',
                'section' => 'Ταυτοποίηση',
                'sort_order' => 14,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        if (Schema::hasColumn('students', 'fylo')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropColumn('fylo');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'fylo')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->string('fylo', 20)->nullable()->after('oikKat');
            });
        }
    }
};
