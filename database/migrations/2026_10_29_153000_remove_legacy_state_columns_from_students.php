<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'OLD_state_foit_stoixeia',
        'OLD_state_foit_input',
        'OLD_state_foit_output',
        'OLD_state_memb_stoixeia',
        'OLD_questStud',
        'OLD_questForeas',
        'OLD_block',
    ];

    public function up(): void
    {
        $columns = array_values(array_filter(
            self::COLUMNS,
            fn (string $column): bool => Schema::hasColumn('students', $column),
        ));

        if ($columns !== []) {
            Schema::table('students', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            foreach (self::COLUMNS as $column) {
                if (! Schema::hasColumn('students', $column)) {
                    $table->integer($column)->default(0);
                }
            }
        });
    }
};
