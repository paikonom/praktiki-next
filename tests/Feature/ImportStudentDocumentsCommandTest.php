<?php

namespace Tests\Feature;

use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportStudentDocumentsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dry_runs_then_imports_approved_documents_without_touching_sources(): void
    {
        Storage::fake('local');
        $source = storage_path('framework/testing/documents-'.Str::uuid());
        File::ensureDirectoryExists($source.'/100');
        File::put($source.'/100/100_ekthesh_Foit.pdf', '%PDF-student');
        File::put($source.'/100/100_f_atlas.pdf', '%PDF-member');

        $departmentId = DB::table('departments')->insertGetId(['atlasId' => 100, 'tmhma' => 'Δοκιμαστικό']);
        $studentId = DB::table('students')->insertGetId([
            'id' => 100,
            'department_id' => $departmentId,
            'fName' => 'Μαρία',
            'fash_id' => $this->internshipPhase('Espa', '2025-2026', $departmentId),
        ]);
        $handler = User::factory()->create(['role' => 'member']);
        DB::table('user_department_access')->insert([
            'user_id' => $handler->id,
            'department_id' => $departmentId,
            'can_manage_espa' => 1,
        ]);

        $this->artisan('documents:import', ['source' => $source])->assertSuccessful();
        $this->assertDatabaseCount('student_documents', 0);

        $this->artisan('documents:import', ['source' => $source, '--write' => true])->assertSuccessful();
        $this->assertDatabaseCount('student_documents', 2);

        $studentDocument = StudentDocument::query()
            ->where('s_id', $studentId)
            ->whereHas('type', fn ($query) => $query->where('code', 'ekthesh_Foit'))
            ->firstOrFail();
        $memberDocument = StudentDocument::query()
            ->whereHas('type', fn ($query) => $query->where('code', 'f_atlas'))
            ->firstOrFail();

        $this->assertSame('approved', $studentDocument->status);
        $this->assertSame('student', $studentDocument->uploaded_by_role);
        $this->assertSame($studentId, $studentDocument->uploaded_by_id);
        $this->assertSame('member', $memberDocument->uploaded_by_role);
        $this->assertSame($handler->id, $memberDocument->uploaded_by_id);
        $this->assertSame($handler->id, $studentDocument->reviewed_by);
        Storage::disk('local')->assertExists($studentDocument->path);
        Storage::disk('local')->assertExists($memberDocument->path);
        $this->assertFileExists($source.'/100/100_ekthesh_Foit.pdf');

        $this->artisan('documents:import', ['source' => $source, '--write' => true])->assertSuccessful();
        $this->assertDatabaseCount('student_documents', 2);

        File::deleteDirectory($source);
    }
}
