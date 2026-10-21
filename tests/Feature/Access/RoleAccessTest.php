<?php

namespace Tests\Feature\Access;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_superadmin_can_open_eo_pages(): void
    {
        $eo = User::factory()->superadmin()->create();

        $this->actingAs($eo)->get('/dashboard')->assertOk();
        $this->actingAs($eo)->get('/clients')->assertOk();
        $this->actingAs($eo)->get('/audit')->assertOk();
    }

    public function test_client_cannot_open_eo_admin_pages(): void
    {
        $client = User::factory()->clientUser()->create();

        $this->actingAs($client)->get('/audit')->assertForbidden();
        $this->actingAs($client)->get('/users')->assertForbidden();
        $this->actingAs($client)->get('/forms')->assertForbidden();
        $this->actingAs($client)->get('/assignments')->assertForbidden();
    }

    public function test_spg_cannot_open_dashboard_or_client_pages(): void
    {
        $spg = User::factory()->spg()->create();

        $this->actingAs($spg)->get('/dashboard')->assertForbidden();
        $this->actingAs($spg)->get('/clients')->assertForbidden();
        $this->actingAs($spg)->get('/campaigns')->assertForbidden();
        $this->actingAs($spg)->get('/tasks')->assertOk();
    }

    public function test_login_redirects_spg_to_tasks(): void
    {
        $spg = User::factory()->spg()->create();

        $this->post('/login', [
            'email' => $spg->email,
            'password' => 'password',
        ])->assertRedirect('/tasks');
    }
}
