<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $supervisors = DB::table('scientific_supervisors')
            ->selectRaw('department_id, MIN(id) AS supervisor_id')
            ->groupBy('department_id')
            ->havingRaw('COUNT(*) = 1')
            ->pluck('supervisor_id', 'department_id');

        foreach ($supervisors as $departmentId => $supervisorId) {
            DB::table('students')
                ->where('department_id', $departmentId)
                ->whereNull('initial_supervisor_id')
                ->update(['initial_supervisor_id' => $supervisorId]);

            DB::table('students')
                ->where('department_id', $departmentId)
                ->whereNull('current_supervisor_id')
                ->update(['current_supervisor_id' => $supervisorId]);
        }
    }

    public function down(): void
    {
        // The assignments may have been edited after this migration and must not be erased.
    }
};
