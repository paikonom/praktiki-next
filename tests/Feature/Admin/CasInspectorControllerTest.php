<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CasInspectorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cas_inspector_is_hidden_when_disabled_and_forbidden_to_members(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.cas-test'))
            ->assertNotFound();

        config(['services.cas.inspect_enabled' => true]);

        $this->actingAs(User::factory()->create(['role' => 'member']))
            ->get(route('admin.cas-test'))
            ->assertForbidden();
    }
}
