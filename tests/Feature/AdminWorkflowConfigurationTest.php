<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkflowAction;
use App\Models\WorkflowSectionRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkflowConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_adds_the_initial_workflow_configuration(): void
    {
        $this->assertDatabaseHas('workflow_actions', ['funding_code' => 'Espa', 'code' => 'input_sumpl', 'source_type' => 'apografiko', 'source_key' => 'input']);
        $payment = WorkflowAction::where('funding_code', 'all')->where('code', 'aitima_plhrwmhs')->firstOrFail();
        $this->assertSame(['approved'], $payment->completed_statuses);
        $this->assertDatabaseMissing('workflow_actions', ['code' => 'all_files_foit']);
        $this->assertDatabaseMissing('workflow_actions', ['code' => 'all_files_foreas']);
        $this->assertDatabaseMissing('workflow_actions', ['code' => 'all_files_scientific_supervisor']);
        $this->assertFalse(\Schema::hasColumn('workflow_section_rules', 'completion_actions'));
        $this->assertFalse(\Schema::hasColumn('workflow_actions', 'available_after'));
        $this->assertFalse(\Schema::hasColumn('workflow_actions', 'email_type'));
        $this->assertFalse(\Schema::hasColumn('document_types', 'available_after'));
    }

    public function test_admin_can_view_and_update_an_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $action = WorkflowAction::where('code', 'stoix_student')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.workflow-actions.index'))->assertOk()->assertSee('Ενέργειες ροής');
        $this->actingAs($admin)->put(route('admin.workflow-actions.update', $action), [
            'funding_code' => 'all', 'section' => 'A', 'sort_order' => 1,
            'code' => 'stoix_student', 'title' => 'Νέος τίτλος', 'responsible_role' => 'student',
            'prerequisites' => ['stoix_forea'],
            'source_type' => 'action', 'source_key' => 'stoix_student', 'active' => 1,
            'allowed_statuses' => ['pending', 'approved'], 'completed_statuses' => ['approved'],
            'counts_for_section' => 1,
        ])->assertRedirect(route('admin.workflow-actions.index'));

        $this->assertDatabaseHas('workflow_actions', ['id' => $action->id, 'title' => 'Νέος τίτλος']);
        $this->assertSame(['stoix_forea'], $action->fresh()->prerequisites);
    }

    public function test_admin_can_update_a_section_rule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rule = WorkflowSectionRule::where('funding_code', 'all')->where('section', 'G')->where('role', 'student')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.workflow-section-rules.update', $rule), [
            'funding_code' => 'all', 'section' => 'G', 'role' => 'student',
            'available_after' => 'internship_end',
            'requires_previous_section' => 0,
        ])->assertRedirect(route('admin.workflow-section-rules.index'));

        $this->assertDatabaseHas('workflow_section_rules', ['id' => $rule->id, 'requires_previous_section' => false]);
    }

    public function test_non_admin_cannot_open_workflow_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'member']))
            ->get(route('admin.workflow-actions.index'))->assertForbidden();
    }
}
