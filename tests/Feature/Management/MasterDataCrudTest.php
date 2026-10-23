<?php

namespace Tests\Feature\Management;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\FormSubmission;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_index_pages_filter_by_search(): void
    {
        $world = $this->world();
        Product::factory()->create(['client_id' => $world['clientA']->id, 'name' => 'Bottled Tea', 'sku' => 'TB-1']);

        $this->actingAs($world['eo'])->get('/products?search=bottled')
            ->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.name', 'Bottled Tea'));
        $this->actingAs($world['eo'])->get('/campaigns?status=closed')
            ->assertInertia(fn ($page) => $page->has('campaigns.data', 0));
    }

    public function test_unused_product_can_be_deleted_but_used_product_cannot(): void
    {
        $world = $this->world();
        $unused = Product::factory()->create(['client_id' => $world['clientA']->id]);

        $this->actingAs($world['eo'])->delete('/products/'.$world['product']->id)->assertSessionHasErrors('product');
        $this->assertNotSoftDeleted($world['product']);

        $this->actingAs($world['eo'])->delete('/products/'.$unused->id)->assertRedirect('/products');
        $this->assertSoftDeleted($unused);
        $this->assertTrue(AuditLog::query()->where('action', 'product.deleted')->exists());
    }

    public function test_only_draft_campaign_without_entries_can_be_deleted(): void
    {
        $world = $this->world();
        $draft = Campaign::factory()->create(['client_id' => $world['clientA']->id]);
        $draftWithEntry = Campaign::factory()->create(['client_id' => $world['clientA']->id]);
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

        $this->actingAs($world['userA'])->delete('/products/'.$world['product']->id)->assertForbidden();
        $this->actingAs($world['userA'])->delete('/campaigns/'.$world['campaign']->id)->assertForbidden();
    }
}
