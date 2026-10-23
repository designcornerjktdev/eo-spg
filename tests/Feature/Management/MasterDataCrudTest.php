<?php

namespace Tests\Feature\Management;

use App\Models\Campaign;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_index_pages_filter_by_search(): void
    {
        $world = $this->world();
        $this->actingAs($world['eo'])->get('/campaigns?status=closed')
            ->assertInertia(fn ($page) => $page->has('campaigns.data', 0));
    }

    public function test_only_draft_campaign_without_entries_can_be_deleted(): void
    {
        $world = $this->world();
        $draft = Campaign::factory()->create();
        $draftWithEntry = Campaign::factory()->create();
        FormSubmission::factory()->create(['campaign_id' => $draftWithEntry->id]);

        $this->actingAs($world['eo'])->delete('/campaigns/'.$draftWithEntry->id)->assertSessionHasErrors('campaign');
        $this->assertNotSoftDeleted($draftWithEntry);

        $this->actingAs($world['eo'])->delete('/campaigns/'.$world['campaign']->id)->assertSessionHasErrors('campaign');
        $this->assertNotSoftDeleted($world['campaign']);

        $this->actingAs($world['eo'])->get('/campaigns/'.$draft->id.'/edit')
            ->assertInertia(fn ($page) => $page->where('deleteBlocker', null));
        $this->actingAs($world['eo'])->delete('/campaigns/'.$draft->id)->assertRedirect('/campaigns');
        $this->assertSoftDeleted($draft);
    }

    public function test_non_superadmin_cannot_delete(): void
    {
        $world = $this->world();

        $this->actingAs($world['userA'])->delete('/campaigns/'.$world['campaign']->id)->assertForbidden();
    }
}
