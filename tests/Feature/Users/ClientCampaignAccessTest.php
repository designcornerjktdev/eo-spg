<?php

namespace Tests\Feature\Users;

use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class ClientCampaignAccessTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_eo_grants_campaign_access_when_creating_a_client_account(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->get('/users/create')
            ->assertInertia(fn ($page) => $page->component('users/create')->has('campaigns', 2));

        $this->actingAs($world['eo'])->post('/users', [
            'name' => 'Brand Manager',
            'username' => 'brand.manager',
            'password' => 'password',
            'role' => 'client',
            'campaign_ids' => [$world['campaign']->id],
        ])->assertSessionHasNoErrors();

        $account = User::query()->where('username', 'brand.manager')->sole();
        $this->assertSame([$world['campaign']->id], $account->campaigns()->pluck('campaigns.id')->all());
    }

    public function test_client_only_sees_entries_of_granted_campaigns(): void
    {
        $world = $this->world();
        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors();
        $this->submitPublicEntry($world['otherLocation'])->assertSessionHasNoErrors();
        [$mine, $theirs] = FormSubmission::query()->orderBy('id')->get()->all();

        $this->actingAs($world['userA'])->get('/submissions')
            ->assertInertia(fn ($page) => $page->has('submissions.data', 1)->where('submissions.data.0.id', $mine->id)->has('campaigns', 1));
        $this->actingAs($world['userA'])->get('/submissions/'.$mine->id)->assertOk();
        $this->actingAs($world['userA'])->get('/submissions/'.$theirs->id)->assertForbidden();
        $this->actingAs($world['userA'])->get('/dashboard')
            ->assertInertia(fn ($page) => $page->where('summary.totals.submissions', 1)->where('summary.totals.campaigns', 1));

        $csv = $this->actingAs($world['userA'])->get('/submissions/export')->streamedContent();
        $this->assertStringContainsString('Sampling Kopi Lepas Haus', $csv);
        $this->assertStringNotContainsString('Campaign IMOBY', $csv);
    }

    public function test_changing_access_takes_effect_immediately(): void
    {
        $world = $this->world();
        $this->submitPublicEntry($world['otherLocation'])->assertSessionHasNoErrors();
        $entry = FormSubmission::query()->sole();

        $this->actingAs($world['userA'])->get('/submissions/'.$entry->id)->assertForbidden();

        $this->actingAs($world['eo'])->put('/users/'.$world['userA']->id, [
            'name' => $world['userA']->name,
            'username' => $world['userA']->username,
            'is_active' => true,
            'campaign_ids' => [$world['otherCampaign']->id],
        ])->assertSessionHasNoErrors();

        $this->actingAs($world['userA'])->get('/submissions/'.$entry->id)->assertOk();
        $this->actingAs($world['userA'])->get('/submissions')->assertInertia(fn ($page) => $page->has('submissions.data', 1));
    }

    public function test_client_without_access_sees_nothing(): void
    {
        $world = $this->world();
        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors();
        $lonely = User::factory()->clientUser()->create();

        $this->actingAs($lonely)->get('/submissions')->assertInertia(fn ($page) => $page->has('submissions.data', 0));
        $this->actingAs($lonely)->get('/dashboard')->assertInertia(fn ($page) => $page->where('summary.totals.submissions', 0));
    }

    public function test_clients_cannot_manage_campaigns(): void
    {
        $world = $this->world();

        $this->actingAs($world['userA'])->get('/campaigns')->assertForbidden();
        $this->actingAs($world['userA'])->get('/campaigns/'.$world['campaign']->id)->assertForbidden();
        $this->actingAs($world['userA'])->put('/users/'.$world['userA']->id, ['campaign_ids' => [$world['otherCampaign']->id]])->assertForbidden();
    }
}
