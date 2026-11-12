<?php

namespace Tests\Feature\Auth;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentAuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_login_requires_matching_amf_and_uuid(): void
    {
        DB::table('students')->insert([
            'AMF' => '1234567890123',
            'uuid' => '9b4998d2-2937-4f69-86d7-681edabf586f',
        ]);

        $this->post(route('student.test_login'), [
            'AMF' => '1234567890123',
            'uuid' => '7fd1da46-0267-48ee-a4e5-23a114aa705a',
        ])->assertSessionHasErrors('AMF');

        $this->post(route('student.test_login'), [
            'AMF' => '1234567890123',
            'uuid' => '9b4998d2-2937-4f69-86d7-681edabf586f',
        ])->assertRedirect(route('student.internships'))
            ->assertSessionHas('student_login_amf', '1234567890123')
            ->assertSessionHas('student_login_source', 'temporary');
    }

    public function test_student_selects_one_of_the_internships_found_by_amf(): void
    {
        $firstId = DB::table('students')->insertGetId([
            'fName' => 'Μαρία',
            'lName' => 'Δοκιμή',
            'AMF' => '1234567890123',
            'fash_id' => $this->internshipPhase('Espa', '2025-2026'),
        ]);
        $secondId = DB::table('students')->insertGetId([
            'fName' => 'Μαρία',
            'lName' => 'Δοκιμή',
            'AMF' => '1234567890123',
            'fash_id' => $this->internshipPhase('Institution', '2024-2025'),
        ]);

        $this->withSession([
            'student_login_amf' => '1234567890123',
            'student_login_source' => 'temporary',
        ]);
        $this->assertGuest('student');

        $this->get(route('student.internships'))
            ->assertOk()
            ->assertSee('Μαρία')
            ->assertSee('Espa')
            ->assertSee('Institution')
            ->assertSee('Πριν την έναρξη')
            ->assertDontSee('Σύνδεση χειριστή');

        $this->post(route('student.internships.select'), ['student_id' => $secondId])
            ->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs(Student::findOrFail($secondId), 'student');
        $this->assertNotSame($firstId, $secondId);
    }

    public function test_unknown_amf_in_session_is_rejected(): void
    {
        $this->withSession(['student_login_amf' => '9999999999999'])
            ->get(route('student.internships'))
            ->assertRedirect(route('student.test_login'))
            ->assertSessionHasErrors('AMF');

        $this->assertGuest('student');
    }

    public function test_canceled_internship_cannot_be_selected(): void
    {
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Μαρία',
            'AMF' => '1234567890123',
            'fash_id' => $this->internshipPhase(),
        ]);
        DB::table('student_actions')->insert(['student_id' => $studentId, 'action' => 'katastash_praktikis', 'status' => 'cancelled']);

        $this->withSession([
            'student_login_amf' => '1234567890123',
            'student_login_source' => 'temporary',
        ])->post(route('student.internships.select'), ['student_id' => $studentId])
            ->assertRedirect(route('student.test_login'))
            ->assertSessionHasErrors('AMF');

        $this->assertGuest('student');
    }

    public function test_existing_session_is_ended_when_the_internship_is_canceled(): void
    {
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Μαρία',
            'AMF' => '1234567890123',
            'fash_id' => $this->internshipPhase(),
        ]);
        DB::table('student_actions')->insert(['student_id' => $studentId, 'action' => 'katastash_praktikis', 'status' => 'cancelled']);

        $this->actingAs(Student::findOrFail($studentId), 'student')
            ->get(route('student.dashboard'))
            ->assertRedirect(route('student.test_login'))
            ->assertSessionHasErrors('AMF');

        $this->assertGuest('student');
    }

    public function test_student_can_log_out(): void
    {
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Μαρία',
            'fash_id' => $this->internshipPhase(),
        ]);

        $response = $this->withSession(['student_login_source' => 'temporary'])
            ->actingAs(Student::findOrFail($studentId), 'student')
            ->post(route('student.logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest('student');
    }

    public function test_cas_response_is_saved_on_the_selected_internship(): void
    {
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Δήμητρα',
            'AMF' => '1452202300142',
            'fash_id' => $this->internshipPhase(),
        ]);
        $casResponse = [
            'user' => 'spm2300142',
            'attributes' => ['uid' => 'spm2300142', 'uoastudentid' => '1452202300142'],
        ];

        $this->withSession([
            'student_login_amf' => '1452202300142',
            'student_login_source' => 'cas',
            'student_cas_response' => $casResponse,
        ])->post(route('student.internships.select'), ['student_id' => $studentId])
            ->assertRedirect(route('student.dashboard'));

        $this->assertSame($casResponse, Student::findOrFail($studentId)->data_CAS);
    }

    public function test_logged_in_student_is_redirected_from_member_login_to_own_dashboard(): void
    {
        $studentId = DB::table('students')->insertGetId([
            'fName' => 'Μαρία',
            'fash_id' => $this->internshipPhase(),
        ]);

        $this->actingAs(Student::findOrFail($studentId), 'student')
            ->get(route('login'))
            ->assertRedirect(route('student.dashboard'));
    }
}
