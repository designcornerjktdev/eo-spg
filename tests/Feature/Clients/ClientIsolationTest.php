<?php

namespace Tests\Feature\Clients;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class ClientIsolationTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_eo_can_create_client_and_bound_account(): void
    {
        $eo = User::factory()->superadmin()->create();

        $this->actingAs($eo)->post('/clients', [
            'name' => 'PT Baru',
            'code' => 'BARU',
            'account_name' => 'Admin Baru',
            'account_email' => 'baru@client.test',
            'account_password' => 'password',
        ])->assertRedirect();

        $client = Client::query()->where('code', 'BARU')->first();
        $this->assertNotNull($client);

        $account = User::query()->where('email', 'baru@client.test')->first();
        $this->assertSame($client->id, $account->client_id);
        $this->assertTrue(Hash::check('password', $account->password));
    }

    public function test_client_cannot_view_another_client(): void
    {
        $world = $this->world();

        $this->actingAs($world['userA'])->get('/clients/'.$world['clientB']->id)->assertForbidden();
        $this->actingAs($world['userA'])->get('/campaigns/'.$world['otherCampaign']->id)->assertForbidden();
        $this->actingAs($world['userA'])->get('/products')->assertOk()->assertDontSee('IMOBY');
    }
}
