<?php

namespace Tests;

use App\Models\Student;
use App\Models\WorkflowAction;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function completeWorkflowSection(Student $student, string $section): void
    {
        foreach (WorkflowAction::activeForFunding($student->fundingCode())->where('section', $section)->where('counts_for_section', true) as $action) {
            $status = $action->completed_statuses[0];

            match ($action->source_type) {
                'action' => $student->actions()->updateOrCreate(['action' => $action->source_key], ['status' => $status]),
                'document' => $student->documents()->updateOrCreate([
                    'document_type_id' => DB::table('document_types')->where('code', $action->source_key)->value('id'),
                    'version' => 1,
                ], ['status' => $status]),
                'apografiko' => $student->apografika()->updateOrCreate(['type' => $action->source_key], ['status' => $status, 'answers' => []]),
                'student_questionnaire' => $student->questionnaire()->updateOrCreate([], ['version' => 1, 'status' => $status, 'submitted_at' => now()]),
                'provider_questionnaire' => $student->providerQuestionnaire()->updateOrCreate([], [
                    'p_id' => $student->provider()->firstOrCreate([])->id,
                    'version' => 1, 'status' => $status, 'submitted_at' => now(),
                ]),
            };
        }
        $student->unsetRelation('actions')->unsetRelation('documents')->unsetRelation('apografika')->unsetRelation('questionnaire')->unsetRelation('providerQuestionnaire');
    }

    protected function approveStudentDetails(int $studentId): void
    {
        DB::table('student_actions')->insert([
            ['student_id' => $studentId, 'action' => 'stoix_student', 'status' => 'approved'],
            ['student_id' => $studentId, 'action' => 'stoix_forea', 'status' => 'approved'],
        ]);
    }

    protected function internshipPhase(
        string $fundingCode = 'Espa',
        string $year = '2025-2026',
        ?int $departmentId = null,
    ): int {
        $departmentId ??= DB::table('departments')->insertGetId([
            'atlasId' => (int) DB::table('departments')->max('atlasId') + 1,
            'tmhma' => 'Δοκιμαστικό Τμήμα',
        ]);
        $fundingId = DB::table('fundings')->where('code', $fundingCode)->value('id');
        $praktikiId = DB::table('praktiki_cycles')
            ->where('funding_id', $fundingId)
            ->where('praxh_year', $year)
            ->value('id')
            ?? DB::table('praktiki_cycles')->insertGetId([
                'funding_id' => $fundingId,
                'praxh_year' => $year,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return DB::table('praktiki_phases')
            ->where('department_id', $departmentId)
            ->where('praktiki_cycle_id', $praktikiId)
            ->value('id')
            ?? DB::table('praktiki_phases')->insertGetId([
                'legacy_fid' => (int) DB::table('praktiki_phases')->max('legacy_fid') + 1,
                'department_id' => $departmentId,
                'praktiki_cycle_id' => $praktikiId,
                'name' => 'Α',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
