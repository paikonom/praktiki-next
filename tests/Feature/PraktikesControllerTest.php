<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PraktikesControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_sees_praktikes_and_their_funding_details(): void
    {
        $user = User::factory()->create();
        $fundingId = DB::table('fundings')->where('code', 'Espa')->value('id');
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => $fundingId,
            'praxh_year' => '2025-2026',
            'details' => json_encode(['K_E_ergou' => '21769', 'kwdikos_OPS' => '6020906']),
        ]);

        $this->actingAs($user)
            ->get(route('praktikes.index', ['year' => '2025-2026', 'funding' => 'Espa']))
            ->assertOk()
            ->assertSee('2025-2026')
            ->assertSee('21769')
            ->assertSee('6020906')
            ->assertSee('Νέα Πρακτική')
            ->assertSee('Επεξεργασία')
            ->assertSee('Μόνο για διαχειριστές');
    }

    public function test_authenticated_user_can_create_a_praktiki_with_its_funding_details(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->post(route('praktikes.store'), [
            'funding' => 'Institution',
            'praxh_year' => '2027-2028',
            'active' => '1',
            'ep_yp_ergou' => 'Μαρία Δοκιμή',
            'K_E_ergou' => '30000',
        ]);

        $response->assertRedirect(route('praktikes.index', [
            'year' => '2027-2028',
            'funding' => 'Institution',
        ]));
        $this->assertDatabaseHas('praktiki_cycles', ['praxh_year' => '2027-2028', 'active' => true]);
        $details = json_decode(DB::table('praktiki_cycles')->where('praxh_year', '2027-2028')->value('details'), true);
        $this->assertSame('Μαρία Δοκιμή', $details['ep_yp_ergou']);
        $this->assertSame('30000', $details['K_E_ergou']);
    }

    public function test_authenticated_user_can_update_a_praktiki_and_its_funding_details(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $fundingId = DB::table('fundings')->where('code', 'InternshipProvider')->value('id');
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => $fundingId,
            'praxh_year' => '2025-2026',
            'active' => true,
        ]);
        $response = $this->actingAs($user)->put(route('praktikes.update', $praktikiId), [
            'funding' => 'InternshipProvider',
            'praxh_year' => '2026-2027',
            'active' => '0',
        ]);

        $response->assertRedirect(route('praktikes.index', [
            'year' => '2026-2027',
            'funding' => 'InternshipProvider',
        ]));
        $this->assertDatabaseHas('praktiki_cycles', [
            'id' => $praktikiId,
            'praxh_year' => '2026-2027',
            'active' => false,
        ]);
        $this->assertNull(DB::table('praktiki_cycles')->where('id', $praktikiId)->value('details'));
    }

    public function test_non_admin_cannot_create_or_edit_praktikes(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $fundingId = DB::table('fundings')->where('code', 'Espa')->value('id');
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => $fundingId,
            'praxh_year' => '2025-2026',
        ]);

        $this->actingAs($user)->get(route('praktikes.create'))->assertForbidden();
        $this->actingAs($user)->get(route('praktikes.edit', $praktikiId))->assertForbidden();
        $this->actingAs($user)->post(route('praktikes.store'), [])->assertForbidden();
    }

    public function test_view_only_admin_cannot_create_or_edit_praktikes(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'view_only' => true]);
        $praktikiId = DB::table('praktiki_cycles')->insertGetId([
            'funding_id' => DB::table('fundings')->where('code', 'Espa')->value('id'),
            'praxh_year' => '2025-2026',
        ]);

        $this->actingAs($user)->get(route('praktikes.create'))->assertForbidden();
        $this->actingAs($user)->get(route('praktikes.edit', $praktikiId))->assertForbidden();
    }
}
