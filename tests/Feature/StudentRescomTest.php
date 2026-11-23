<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentRescomTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_rescom_uses_the_cycle_project_and_protects_against_repeat_submissions(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $url = 'https://rescom.example.test/api/contract';
        config(['services.rescom.url' => $url, 'services.rescom.token' => 'test-token']);
        $student = Student::forceCreate([
            'fash_id' => $this->internshipPhase(),
            'startDate' => '2026-01-01', 'endDate' => '2026-03-31', 'payTotal' => '1000',
            'afm' => '012345678', 'amka' => '12345678901', 'iban' => 'GR123',
            'dob' => '2000-01-01', 'fylo_rescom' => 1, 'fName' => 'ΟΝΟΜΑ', 'lName' => 'ΕΠΩΝΥΜΟ',
            'doy_rescom' => 1, 'ama' => '12345', 'fatFName' => 'ΠΑΤΕΡΑΣ', 'momFName' => 'ΜΗΤΕΡΑ',
            'adt' => 'ΑΒ123456', 'numChild' => 0, 'email' => 'student@example.test',
            'address_odos' => 'ΟΔΟΣ 1', 'address_tk' => '12345', 'address_polh_rescom' => 1, 'xwra_rescom' => 300,
        ]);
        $student->phase->praktiki->forceFill(['details' => ['K_E_ergou' => '98765']])->save();
        DB::table('providers')->insert([
            's_id' => $student->id, 'f_epwnymia' => 'ΦΟΡΕΑΣ', 'f_afm' => '090166291',
            'f_doy_rescom' => 1, 'dhm_idiwt_rescom' => 1, 'f_proeleush_rescom' => 1,
            'f_odos' => 'ΟΔΟΣ 2', 'f_tk' => '12345', 'f_polh_rescom' => 1, 'f_xwra_rescom' => 300, 'f_thl' => '2100000000',
        ]);

        $departmentId = $student->phase->department_id;
        $student->forceFill(['department_id' => $departmentId])->save();
        $member = User::factory()->create(['role' => 'member', 'view_only' => false]);
        $viewOnly = User::factory()->create(['role' => 'member', 'view_only' => true]);
        DB::table('user_department_access')->insert([
            ['user_id' => $member->id, 'department_id' => $departmentId],
            ['user_id' => $viewOnly->id, 'department_id' => $departmentId],
        ]);
        $this->freezeTime();
        Http::preventStrayRequests();
        Http::fake([$url => Http::sequence()
            ->push(['WasSuccessful' => false, 'ErrorMessage' => 'Δοκιμαστική απόρριψη'])
            ->push(['WasSuccessful' => true, 'ContractId' => 10])
            ->push('unavailable', 503)]);
        $this->actingAs(User::factory()->create(['role' => 'member']))
            ->post(route('students.rescom.store', $student))->assertForbidden();
        $this->actingAs($viewOnly)
            ->post(route('students.rescom.store', $student))->assertForbidden();
        $preview = $this->actingAs($user)->get(route('students.rescom.preview', $student))
            ->assertOk()->assertSee('98765')->assertDontSee('test-token');
        $signature = $preview->viewData('signature');
        $route = route('students.rescom.store', $student);
        Http::assertNothingSent();

        $this->post($route, ['signature' => $signature])
            ->assertSessionHas('status_type', 'danger')
            ->assertSessionHas('status', 'Το RESCOM απέρριψε την αίτηση. Δοκιμαστική απόρριψη');
        $this->assertSame('rejected', $student->fresh()->rescom_resp['_submission']['status']);
        $this->assertSame($user->id, $student->fresh()->rescom_resp['_submission']['sent_by_user_id']);
        $this->actingAs($member)->get(route('students.rescom.preview', $student))
            ->assertOk()->assertSee('Επιβεβαίωση αποστολής');
        $this->post($route, ['signature' => $signature])->assertRedirect(route('students.show', $student));
        $this->assertSame(10, $student->fresh()->rescom_resp['ContractId']);
        $this->assertEquals([
            'status' => 'accepted',
            'sent_by_user_id' => $member->id,
            'sent_at' => now()->toIso8601String(),
        ], $student->fresh()->rescom_resp['_submission']);
        $this->post($route, ['signature' => $signature])->assertSessionHas('status_type', 'danger');
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === $url
            && $request['Authentication'] === 'test-token'
            && $request['Data']['Έργου_Αριθμός'] === '98765');

        Student::query()->whereKey($student->id)->update(['rescom_resp' => null]);
        $this->post($route, ['signature' => $signature])->assertSessionHas('status_type', 'danger');
        $this->assertSame('pending', $student->fresh()->rescom_resp['_submission']['status']);
        $this->post($route, ['signature' => $signature])->assertSessionHas('status_type', 'danger');
        Http::assertSentCount(3);
    }
}
