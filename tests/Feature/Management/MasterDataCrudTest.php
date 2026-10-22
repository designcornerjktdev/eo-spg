<?php

namespace Tests\Feature\Management;

use App\Enums\AssignmentStatus;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    private function submitOne(array $world): void
    {
        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/submissions', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ])->assertSessionHasNoErrors();
    }

    public function test_index_pages_filter_by_search(): void
    {
        $world = $this->world();
        Product::factory()->create(['client_id' => $world['clientA']->id, 'name' => 'Bottled Tea', 'sku' => 'TB-1']);

        $this->actingAs($world['eo'])->get('/products?search=bottled')
            ->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.name', 'Bottled Tea'));
        $this->actingAs($world['eo'])->get('/campaigns?status=closed')
            ->assertInertia(fn ($page) => $page->has('campaigns.data', 0));
        $this->actingAs($world['eo'])->get('/assignments?search='.urlencode($world['spg']->name))
            ->assertInertia(fn ($page) => $page->has('assignments.data', 1));
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

    public function test_only_empty_draft_campaign_can_be_deleted(): void
    {
        $world = $this->world();
        $draft = Campaign::factory()->create(['client_id' => $world['clientA']->id]);

        $this->actingAs($world['eo'])->delete('/campaigns/'.$world['campaign']->id)->assertSessionHasErrors('campaign');
        $this->assertNotSoftDeleted($world['campaign']);

        $this->actingAs($world['eo'])->get('/campaigns/'.$draft->id.'/edit')
            ->assertInertia(fn ($page) => $page->where('deleteBlocker', null));
        $this->actingAs($world['eo'])->delete('/campaigns/'.$draft->id)->assertRedirect('/campaigns');
        $this->assertSoftDeleted($draft);
    }

    public function test_assignment_without_data_can_be_deleted(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->delete('/assignments/'.$world['assignment']->id)->assertRedirect('/assignments');

        $this->assertModelMissing($world['assignment']);
        $this->assertTrue(AuditLog::query()->where('action', 'assignment.deleted')->exists());
    }

    public function test_assignment_with_data_is_cancelled_instead_of_deleted(): void
    {
        $world = $this->world();
        $this->submitOne($world);

        $this->actingAs($world['eo'])->delete('/assignments/'.$world['assignment']->id)->assertSessionHasErrors('assignment');
        $this->assertModelExists($world['assignment']);

        $this->actingAs($world['eo'])->post('/assignments/'.$world['assignment']->id.'/cancel')->assertRedirect('/assignments');
        $this->assertSame(AssignmentStatus::Cancelled, $world['assignment']->refresh()->status);

        // The SPG can no longer submit to a cancelled assignment.
        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/submissions', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ])->assertSessionHasErrors('assignment');
    }

    public function test_cancelled_assignment_frees_spg_schedule(): void
    {
        $world = $this->world();
        $existing = $world['assignment'];
        $payload = [
            'campaign_id' => $world['campaign']->id,
            'location_id' => $world['location']->id,
            'form_template_id' => $world['form']->id,
            'user_id' => $world['spg']->id,
            'starts_at' => $existing->starts_at->toDateTimeString(),
            'ends_at' => $existing->ends_at->toDateTimeString(),
        ];

        $this->actingAs($world['eo'])->post('/assignments', $payload)->assertSessionHasErrors('starts_at');

        $this->actingAs($world['eo'])->post('/assignments/'.$existing->id.'/cancel');
        $this->actingAs($world['eo'])->post('/assignments', $payload)->assertSessionHasNoErrors();

        $this->assertSame(2, Assignment::query()->where('user_id', $world['spg']->id)->count());
    }

    public function test_non_superadmin_cannot_delete_or_cancel(): void
    {
        $world = $this->world();

        $this->actingAs($world['userA'])->delete('/products/'.$world['product']->id)->assertForbidden();
        $this->actingAs($world['userA'])->delete('/campaigns/'.$world['campaign']->id)->assertForbidden();
        $this->actingAs($world['spg'])->post('/assignments/'.$world['assignment']->id.'/cancel')->assertForbidden();
    }
}
