<?php

namespace Tests\Feature\Clients;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_superadmin_can_search_clients(): void
    {
        $eo = User::factory()->superadmin()->create();
        Client::factory()->create(['name' => 'PT Alpha', 'code' => 'ALPHA']);
        Client::factory()->create(['name' => 'PT Beta', 'code' => 'BETA']);

        $this->actingAs($eo)->get('/clients?search=alp')
            ->assertInertia(fn ($page) => $page->component('clients/index')->has('clients.data', 1)->where('clients.data.0.code', 'ALPHA'));
    }

    public function test_superadmin_can_edit_client(): void
    {
        $eo = User::factory()->superadmin()->create();
        $client = Client::factory()->create(['code' => 'LAMA']);

        $this->actingAs($eo)->get('/clients/'.$client->id.'/edit')->assertOk();
        $this->actingAs($eo)->put('/clients/'.$client->id, ['name' => 'PT Baru', 'code' => 'BARU', 'notes' => null])
            ->assertSessionHasNoErrors();

        $this->assertSame('BARU', $client->refresh()->code);
        $this->assertTrue(AuditLog::query()->where('action', 'client.updated')->exists());
    }

    public function test_client_without_campaign_or_product_can_be_deleted_and_accounts_deactivated(): void
    {
        $eo = User::factory()->superadmin()->create();
        $client = Client::factory()->create();
        $account = User::factory()->clientUser($client)->create(['username' => 'akunclient']);

        $this->actingAs($eo)->delete('/clients/'.$client->id)->assertRedirect('/clients');

        $this->assertSoftDeleted($client);
        $this->assertFalse($account->refresh()->is_active);

        $this->post('/logout');
        $this->post('/login', ['login' => 'akunclient', 'password' => 'password'])->assertSessionHasErrors('login');
    }

    public function test_client_with_campaign_cannot_be_deleted(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->delete('/clients/'.$world['clientA']->id)->assertSessionHasErrors('client');

        $this->assertNotSoftDeleted($world['clientA']);
        $this->assertTrue($world['userA']->refresh()->is_active);
    }

    public function test_client_user_cannot_edit_or_delete(): void
    {
        $world = $this->world();

        $this->actingAs($world['userA'])->get('/clients/'.$world['clientA']->id.'/edit')->assertForbidden();
        $this->actingAs($world['userA'])->delete('/clients/'.$world['clientA']->id)->assertForbidden();
    }
}
