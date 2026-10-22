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

        $this->actingAs($world['spg'])->get('/tasks')->assertOk()->assertSee('Sampling Kopi Lepas Haus');
        $this->actingAs($world['otherSpg'])->get('/tasks')->assertOk()->assertDontSee('Sampling Kopi Lepas Haus');

        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/submissions', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
            'add_another' => true,
        ])->assertRedirect('/tasks/'.$world['assignment']->id);

        $this->assertSame(1, FormSubmission::query()->count());
        $this->actingAs($world['spg'])->get('/tasks')->assertOk();

        $submission = FormSubmission::query()->first();
        $this->actingAs($world['eo'])->get('/submissions?campaign_id='.$world['campaign']->id)->assertOk();
        $this->actingAs($world['userA'])->get('/submissions/'.$submission->id)->assertOk();
        $this->actingAs($world['userB'])->get('/submissions/'.$submission->id)->assertForbidden();
        $this->actingAs($world['userA'])->get('/dashboard')->assertOk();
        $this->actingAs($world['userA'])->get('/submissions/export')->assertOk();
    }
}
