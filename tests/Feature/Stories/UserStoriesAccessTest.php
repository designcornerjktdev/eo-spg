<?php

namespace Tests\Feature\Stories;

use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class UserStoriesAccessTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_u1_to_u12_happy_path_and_isolation(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->get('/clients/'.$world['clientA']->id)->assertOk();
        $this->actingAs($world['userA'])->get('/products')->assertOk()->assertSee('Kopi Lepas Haus');
        $this->actingAs($world['userB'])->get('/products')->assertOk()->assertDontSee('Kopi Lepas Haus');

        // A consumer opens the location link without logging in and submits an entry.
        $this->get('/f/'.$world['location']->public_token)->assertOk()->assertSee('Sampling Kopi Lepas Haus');
        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors()->assertSessionHas('submitted', true);

        $this->assertSame(1, FormSubmission::query()->count());
        $this->assertSame($world['location']->id, FormSubmission::query()->first()->location_id);

        $submission = FormSubmission::query()->first();
        $this->actingAs($world['eo'])->get('/submissions?campaign_id='.$world['campaign']->id)->assertOk();
        $this->actingAs($world['userA'])->get('/submissions/'.$submission->id)->assertOk();
        $this->actingAs($world['userB'])->get('/submissions/'.$submission->id)->assertForbidden();
        $this->actingAs($world['userA'])->get('/dashboard')->assertOk();
        $this->actingAs($world['userA'])->get('/submissions/export')->assertOk();
    }
}
