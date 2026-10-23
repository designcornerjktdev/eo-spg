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
        $this->actingAs($eo)->get('/campaigns')->assertOk();
        $this->actingAs($eo)->get('/audit')->assertOk();
    }

    public function test_client_cannot_open_eo_admin_pages(): void
    {
        $client = User::factory()->clientUser()->create();

        $this->actingAs($client)->get('/audit')->assertForbidden();
        $this->actingAs($client)->get('/users')->assertForbidden();
        $this->actingAs($client)->get('/forms')->assertForbidden();
        $this->actingAs($client)->get('/campaigns')->assertForbidden();
    }

    public function test_removed_pages_no_longer_exist(): void
    {
        $eo = User::factory()->superadmin()->create();

        foreach (['/tasks', '/assignments', '/clients', '/products'] as $url) {
            $this->actingAs($eo)->get($url)->assertNotFound();
        }
    }

    public function test_login_redirects_client_to_dashboard(): void
    {
        $client = User::factory()->clientUser()->create();

        $this->post('/login', [
            'login' => $client->username,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
    }
}
