<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Support\RescomReferenceData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentAgreementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_handler_can_preview_each_funding_agreement(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['Espa', 'Institution', 'InternshipProvider'] as $funding) {
            $student = $this->studentFor($funding);

            $this->actingAs($admin)
                ->get(route('students.agreement.preview', $student))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_provider_addresses_use_structured_values_and_keep_the_legacy_internship_fallback(): void
    {
        $student = $this->studentFor('Espa');
        $provider = $student->provider()->firstOrFail();
        $city = app(RescomReferenceData::class)->label('cities', 15875);

        $this->assertSame($city.', Οδός Έδρας 1, ΤΚ 12345', $provider->headquartersAddress());
        $this->assertSame($city.', Οδός Πρακτικής 2, ΤΚ 12345', $provider->internshipAddress());
        $this->assertSame('Νόμιμος Εκπρόσωπος, Διευθυντής', $provider->legalRepresentative());

        $provider->forceFill([
            'f_ektPrakt_polh_rescom' => null,
            'f_ektPrakt_odos' => null,
            'f_ektPrakt_tk' => null,
            'f_topos_ekt_prakt' => 'ΠΑΛΙΟΣ ΤΟΠΟΣ ΠΡΑΚΤΙΚΗΣ',
        ])->save();

        $this->assertSame('ΠΑΛΙΟΣ ΤΟΠΟΣ ΠΡΑΚΤΙΚΗΣ', $provider->fresh()->internshipAddress());
    }

    public function test_agreement_panel_only_previews_and_links_to_document_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->studentFor('Institution');

        $this->actingAs($admin)
            ->get(route('students.show', ['student' => $student, 'panel' => 'agreement']))
            ->assertOk()
            ->assertSee('Προεπισκόπηση PDF')
            ->assertSee('Μετάβαση στα Έντυπα και ιστορικό')
            ->assertDontSee('Δημοσίευση');
    }

    public function test_handler_without_department_access_cannot_preview(): void
    {
        $student = $this->studentFor('Espa');
        $handler = User::factory()->create(['role' => 'member']);

        $this->actingAs($handler)->get(route('students.agreement.preview', $student))->assertNotFound();
    }

    private function studentFor(string $fundingCode, bool $complete = true): Student
    {
        $suffix = DB::table('students')->count() + 1;
        $departmentId = DB::table('departments')->insertGetId([
            'atlasId' => 9000 + $suffix,
            'tmhma' => 'Δοκιμαστικό Τμήμα',
            'polh' => 'ΑΘΗΝΑ',
            'proedros' => 'Πρόεδρος Τμήματος',
            'proedros_vathm' => 'Καθηγητής',
        ]);
        $supervisorId = DB::table('scientific_supervisors')->insertGetId([
            'department_id' => $departmentId,
            'fName' => 'Επιστημονικός',
            'lName' => 'Υπεύθυνος',
            'email' => 'supervisor@example.test',
            'apofash_tmhmatos' => 'Απόφαση 10/2026',
        ]);
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => DB::table('fundings')->where('code', $fundingCode)->value('id'),
            'praxh_year' => 'TEST-'.$suffix,
        ]);

        $cycleDetails = match ($fundingCode) {
            'Espa' => [
                'ep_yp_ergou' => 'Υπεύθυνος Έργου',
                'K_E_ergou' => '12345',
                'titlos_praxhs' => 'Πράξη Πρακτικής Άσκησης',
                'titlos_programmatos' => 'Πρόγραμμα Δοκιμής',
                'titlos_ypoergou' => 'Υποέργο Πρακτικής Άσκησης',
                'kwdikos_OPS' => '6000000',
            ],
            'Institution' => [
                'ep_yp_ergou' => 'Ιδρυματικός Υπεύθυνος',
                'K_E_ergou' => '54321',
            ],
            'InternshipProvider' => null,
        };
        DB::table('praktiki_cycles')->where('id', $praktikiId)->update([
            'details' => $cycleDetails === null ? null : json_encode($cycleDetails),
        ]);

        $phaseDetails = match ($fundingCode) {
            'Espa' => [
                'apofash_tmhmatos' => 'Απόφαση Τμήματος',
                'apofash_ELKE' => 'Απόφαση ΕΛΚΕ',
                'ADA_apofash_ELKE' => 'ΑΔΑ123',
            ],
            'Institution' => ['apofash_ELKE' => 'Απόφαση ΕΛΚΕ'],
            'InternshipProvider' => ['apofash_tmhmatos' => 'Απόφαση Τμήματος'],
        };

        $phaseId = DB::table('praktiki_phases')->insertGetId([
            'legacy_fid' => 8000 + $suffix,
            'praktiki_cycle_id' => $praktikiId,
            'department_id' => $departmentId,
            'name' => 'Α',
            'details' => json_encode($phaseDetails),
        ]);

        $studentId = DB::table('students')->insertGetId([
            'department_id' => $departmentId,
            'fash_id' => $phaseId,
            'supervisor_id' => $supervisorId,
            'fName' => 'Μαρία',
            'lName' => 'Δοκιμή',
            'AMF' => 'TEST'.$suffix,
            'afm' => $complete ? '123456789' : null,
            'amka' => '01010100000',
            'startDate' => '2026-01-01',
            'endDate' => '2026-03-01',
            'numMhnes' => '2',
            'wresPerWeek' => '40',
        ]);
        DB::table('providers')->insert([
            's_id' => $studentId,
            'f_epwnymia' => $complete ? 'Δοκιμαστικός Φορέας' : null,
            'f_afm' => '987654321',
            'f_polh_rescom' => 15875,
            'f_xwra_rescom' => 300,
            'f_odos' => 'Οδός Έδρας 1',
            'f_tk' => '12345',
            'f_ekprFname' => 'Νόμιμος',
            'f_ekprLname' => 'Εκπρόσωπος',
            'f_ekprIdiothta' => 'Διευθυντής',
            'f_ektPrakt_polh_rescom' => 15875,
            'f_ektPrakt_odos' => 'Οδός Πρακτικής 2',
            'f_ektPrakt_tk' => '12345',
            'f_epopt_foreaFname' => 'Επόπτης',
            'f_epopt_foreaLname' => 'Φορέα',
            'f_epopt_foreaThesh' => 'Υπεύθυνος',
            'f_antikeim_Praktikhs' => 'Δοκιμαστικό αντικείμενο',
            'f_apotel_Praktikhs' => 'Δοκιμαστικά αποτελέσματα',
        ]);

        return Student::findOrFail($studentId);
    }
}
