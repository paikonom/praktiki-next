<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE students
            SET data_CAS = JSON_OBJECT('legacy_serialized', data_CAS)
            WHERE data_CAS IS NOT NULL AND JSON_VALID(data_CAS) = 0
        SQL);
        DB::statement('ALTER TABLE students MODIFY data_CAS JSON NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE students MODIFY data_CAS TEXT NULL');
    }
};
