<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->addStatus('action', ['stoix_student', 'stoix_forea'], 'draft');
        $this->addStatus('apografiko', ['input', 'output'], 'submitted');
    }

    public function down(): void
    {
        $this->removeStatus('action', ['stoix_student', 'stoix_forea'], 'draft');
        $this->removeStatus('apografiko', ['input', 'output'], 'submitted');
    }

    private function addStatus(string $sourceType, array $sourceKeys, string $status): void
    {
        DB::table('workflow_actions')
            ->where('source_type', $sourceType)
            ->whereIn('source_key', $sourceKeys)
            ->get(['id', 'allowed_statuses'])
            ->each(function (object $action) use ($status): void {
                $allowedStatuses = json_decode($action->allowed_statuses, true) ?: [];

                if (! in_array($status, $allowedStatuses, true)) {
                    $allowedStatuses[] = $status;
                    DB::table('workflow_actions')->where('id', $action->id)->update([
                        'allowed_statuses' => json_encode($allowedStatuses),
                    ]);
                }
            });
    }

    private function removeStatus(string $sourceType, array $sourceKeys, string $status): void
    {
        DB::table('workflow_actions')
            ->where('source_type', $sourceType)
            ->whereIn('source_key', $sourceKeys)
            ->get(['id', 'allowed_statuses'])
            ->each(function (object $action) use ($status): void {
                $allowedStatuses = array_values(array_filter(
                    json_decode($action->allowed_statuses, true) ?: [],
                    fn (string $allowedStatus): bool => $allowedStatus !== $status,
                ));

                DB::table('workflow_actions')->where('id', $action->id)->update([
                    'allowed_statuses' => json_encode($allowedStatuses),
                ]);
            });
    }
};
