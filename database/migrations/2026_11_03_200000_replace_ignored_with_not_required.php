<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('student_actions')->where('status', 'ignored')->update(['status' => 'not_required']);
        DB::table('student_documents')->where('status', 'ignored')->update(['status' => 'not_required']);
        $this->replaceWorkflowStatus('ignored', 'not_required');
    }

    public function down(): void
    {
        DB::table('student_actions')->where('status', 'not_required')->update(['status' => 'ignored']);
        DB::table('student_documents')->where('status', 'not_required')->update(['status' => 'ignored']);
        $this->replaceWorkflowStatus('not_required', 'ignored');
    }

    private function replaceWorkflowStatus(string $oldStatus, string $newStatus): void
    {
        DB::table('workflow_actions')
            ->whereIn('source_type', ['action', 'document'])
            ->get(['id', 'allowed_statuses', 'completed_statuses'])
            ->each(function (object $action) use ($oldStatus, $newStatus): void {
                DB::table('workflow_actions')->where('id', $action->id)->update([
                    'allowed_statuses' => $this->replaceInList($action->allowed_statuses, $oldStatus, $newStatus),
                    'completed_statuses' => $this->replaceInList($action->completed_statuses, $oldStatus, $newStatus),
                ]);
            });
    }

    private function replaceInList(?string $json, string $oldStatus, string $newStatus): string
    {
        $statuses = array_map(
            fn (string $status): string => $status === $oldStatus ? $newStatus : $status,
            json_decode($json ?? '[]', true) ?: [],
        );

        return json_encode(array_values(array_unique($statuses)));
    }
};
