<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_actions', function (Blueprint $table) {
            $table->json('allowed_statuses')->nullable()->after('source_key');
            $table->json('completed_statuses')->nullable()->after('allowed_statuses');
            $table->json('status_prerequisites')->nullable()->after('completed_statuses');
            $table->boolean('counts_for_section')->default(true)->after('status_prerequisites');
        });

        $statusRules = [
            'action' => [['pending', 'submitted', 'approved', 'rejected', 'ignored'], ['approved', 'ignored']],
            'document' => [['submitted', 'approved', 'rejected', 'ignored'], ['approved', 'ignored']],
            'apografiko' => [['draft', 'processing', 'completed', 'failed', 'not_required', 'cancelled'], ['completed', 'not_required']],
            'questionnaire' => [['draft', 'submitted', 'rejected'], ['submitted']],
        ];

        foreach ($statusRules as $source => [$allowed, $completed]) {
            $sourceTypes = $source === 'questionnaire'
                ? ['student_questionnaire', 'provider_questionnaire']
                : [$source];
            DB::table('workflow_actions')->whereIn('source_type', $sourceTypes)->update([
                'allowed_statuses' => json_encode($allowed),
                'completed_statuses' => json_encode($completed),
                'status_prerequisites' => json_encode([]),
            ]);
        }

        DB::table('workflow_actions')->whereIn('code', ['all_files_foit', 'all_files_foreas', 'all_files_scientific_supervisor'])->update([
            'allowed_statuses' => json_encode(['pending', 'submitted']),
            'completed_statuses' => json_encode(['submitted']),
            'counts_for_section' => false,
        ]);

        DB::table('workflow_actions')->whereIn('code', [
            'section_a_approval', 'section_b_approval', 'section_g_approval', 'finish_pa',
        ])->delete();

        DB::table('workflow_actions')->updateOrInsert(
            ['funding_code' => 'all', 'code' => 'aitima_plhrwmhs'],
            [
                'section' => 'D', 'sort_order' => 1, 'title' => 'Κατάσταση αιτήματος πληρωμής',
                'responsible_role' => 'member', 'available_after' => 'internship_end',
                'prerequisites' => json_encode([]), 'source_type' => 'action', 'source_key' => 'aitima_plhrwmhs',
                'allowed_statuses' => json_encode(['pending', 'submitted', 'approved', 'rejected']),
                'completed_statuses' => json_encode(['approved']), 'status_prerequisites' => json_encode([]),
                'counts_for_section' => true, 'active' => true, 'updated_at' => now(), 'created_at' => now(),
            ],
        );
        DB::table('workflow_actions')->where('code', 'aitima_plhrwmhs')->where('funding_code', '!=', 'all')->delete();

        DB::table('workflow_actions')->updateOrInsert(
            ['funding_code' => 'all', 'code' => 'katastash_praktikis'],
            [
                'section' => 'D', 'sort_order' => 2, 'title' => 'Κατάσταση πρακτικής άσκησης',
                'responsible_role' => 'member', 'available_after' => 'always',
                'prerequisites' => json_encode([]), 'source_type' => 'action', 'source_key' => 'katastash_praktikis',
                'allowed_statuses' => json_encode(['active', 'cancelled', 'completed']),
                'completed_statuses' => json_encode(['completed']),
                'status_prerequisites' => json_encode(['completed' => ['aitima_plhrwmhs']]),
                'counts_for_section' => true, 'active' => true, 'updated_at' => now(), 'created_at' => now(),
            ],
        );

        DB::table('workflow_section_rules')->update(['completion_actions' => json_encode([])]);
        DB::table('fields')->where('card_section', 'office')->whereIn('field', [
            'all_files_foit', 'all_files_foreas', 'all_files_scientific_supervisor', 'finish_pa', 'aitima_plhrwmhs', 'status',
        ])->delete();

        DB::statement("INSERT INTO student_actions (student_id, action, status, reviewed_at, reviewed_by, review_comment, created_at, updated_at)
            SELECT s.id, 'katastash_praktikis',
                CASE WHEN s.status = 'cancelled' THEN 'cancelled' WHEN finish.status IN ('approved','ignored') THEN 'completed' ELSE 'active' END,
                CASE WHEN s.status = 'cancelled' THEN s.cancelled_at WHEN finish.status IN ('approved','ignored') THEN finish.reviewed_at ELSE NULL END,
                CASE WHEN s.status = 'cancelled' THEN s.cancelled_by WHEN finish.status IN ('approved','ignored') THEN finish.reviewed_by ELSE NULL END,
                CASE WHEN s.status = 'cancelled' THEN s.cancellation_reason ELSE finish.review_comment END,
                NOW(), NOW()
            FROM students s
            LEFT JOIN student_actions finish ON finish.student_id = s.id AND finish.action = 'finish_pa'
            ON DUPLICATE KEY UPDATE status = VALUES(status), reviewed_at = VALUES(reviewed_at), reviewed_by = VALUES(reviewed_by), review_comment = VALUES(review_comment), updated_at = NOW()");

        DB::table('student_actions')->whereIn('action', [
            'section_a_approval', 'section_b_approval', 'section_g_approval', 'finish_pa',
        ])->delete();

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['status', 'cancelled_at', 'cancelled_by', 'cancellation_reason']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
        });

        Schema::table('workflow_actions', function (Blueprint $table) {
            $table->dropColumn(['allowed_statuses', 'completed_statuses', 'status_prerequisites', 'counts_for_section']);
        });
    }
};
