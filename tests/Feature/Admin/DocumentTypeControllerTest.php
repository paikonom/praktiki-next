<?php

namespace Tests\Feature\Admin;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_document_type_with_fundings_and_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.document-types.store'), [
            'code' => 'new_document', 'title' => 'Νέο έγγραφο', 'max_size_mb' => 20,
            'active' => 1, 'sort_order' => 50,
            'fundings' => ['Espa', 'Institution'],
            'view_roles' => ['student', 'scientific_supervisor', 'member'], 'upload_roles' => ['student', 'scientific_supervisor'],
        ])->assertRedirect(route('admin.document-types.index'));

        $this->assertDatabaseHas('document_types', ['code' => 'new_document', 'max_size_mb' => 20]);
        $typeId = (int) \DB::table('document_types')->where('code', 'new_document')->value('id');
        $this->assertDatabaseHas('document_type_fundings', ['document_type_id' => $typeId, 'funding_code' => 'Institution']);
        $this->assertDatabaseHas('document_type_roles', ['document_type_id' => $typeId, 'role' => 'student', 'can_upload' => 1]);
        $this->assertDatabaseHas('document_type_roles', ['document_type_id' => $typeId, 'role' => 'scientific_supervisor', 'can_upload' => 1]);
    }

    public function test_non_admin_cannot_manage_document_types(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get(route('admin.document-types.index'))->assertForbidden();
    }

    public function test_admin_can_edit_a_type_without_changing_its_unique_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $type = DocumentType::where('code', 'ekthesh_Forea')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.document-types.update', $type), [
            'code' => $type->code, 'title' => 'Νέος τίτλος', 'max_size_mb' => 15,
            'active' => 1, 'sort_order' => 6,
            'fundings' => ['Espa'], 'view_roles' => ['provider', 'member'],
            'upload_roles' => ['provider', 'member'],
        ])->assertRedirect(route('admin.document-types.index'));

        $this->assertDatabaseHas('document_types', ['id' => $type->id, 'code' => 'ekthesh_Forea', 'title' => 'Νέος τίτλος']);
    }
}
