<?php

namespace Tests\Feature\PublicForm;

use App\Enums\CampaignStatus;
use App\Enums\FormStatus;
use App\Models\CampaignLocation;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class PublicFormTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_every_location_gets_a_unique_public_token(): void
    {
        $world = $this->world();
        $other = CampaignLocation::factory()->create(['campaign_id' => $world['campaign']->id]);

        $this->assertSame(32, strlen($world['location']->public_token));
        $this->assertNotSame($world['location']->public_token, $other->public_token);
    }

    public function test_guest_can_open_link_and_submit_an_entry(): void
    {
        $world = $this->world();

        $this->get('/f/'.$world['location']->public_token)->assertOk()->assertInertia(fn ($page) => $page
            ->component('public/form')
            ->where('open', true)
            ->where('campaign', 'Sampling Kopi Lepas Haus')
            ->where('location', 'PIK Avenue')
            ->has('form.fields', 9));

        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors()->assertSessionHas('submitted', true);

        $entry = FormSubmission::query()->sole();
        $this->assertSame($world['location']->id, $entry->location_id);
        $this->assertSame($world['campaign']->id, $entry->campaign_id);
        $this->assertTrue($entry->qualified);
        $this->assertSame(FormStatus::Locked, $world['form']->fresh()->status);
        $this->assertGuest();
    }

    public function test_link_is_closed_unless_campaign_is_active(): void
    {
        $world = $this->world();

        foreach ([CampaignStatus::Draft, CampaignStatus::Closed] as $status) {
            $world['campaign']->update(['status' => $status]);

            $this->get('/f/'.$world['location']->public_token)->assertInertia(fn ($page) => $page->where('open', false));
            $this->submitPublicEntry($world['location'])->assertSessionHasErrors('form');
        }

        $this->assertSame(0, FormSubmission::query()->count());
    }

    public function test_link_is_closed_without_a_published_form(): void
    {
        $world = $this->world();
        $world['form']->update(['status' => FormStatus::Draft]);

        $this->get('/f/'.$world['location']->public_token)->assertInertia(fn ($page) => $page->where('open', false)->where('form', null));
        $this->submitPublicEntry($world['location'])->assertSessionHasErrors('form');
    }

    public function test_entries_use_the_newest_published_form_version(): void
    {
        $world = $this->world();
        $newer = FormTemplate::factory()->create([
            'campaign_id' => $world['campaign']->id,
            'name' => 'Short form',
            'version' => 2,
            'status' => FormStatus::Published,
            'requires_consent' => false,
        ]);
        $newer->fields()->create(['key' => 'nama', 'label' => 'Name', 'type' => 'short_text', 'required' => true, 'sort_order' => 1]);

        $this->get('/f/'.$world['location']->public_token)->assertInertia(fn ($page) => $page->where('form.name', 'Short form')->has('form.fields', 1));
        $this->post('/f/'.$world['location']->public_token, ['answers' => ['nama' => 'Andi']])->assertSessionHasNoErrors();

        $this->assertSame($newer->id, FormSubmission::query()->sole()->form_template_id);
    }

    public function test_required_answers_and_consent_are_validated(): void
    {
        $world = $this->world();

        $this->post('/f/'.$world['location']->public_token, ['answers' => ['nama' => 'Andi'], 'consent_accepted' => true])
            ->assertSessionHasErrors('answers.usia');
        $this->post('/f/'.$world['location']->public_token, ['answers' => $this->validAnswers(), 'consent_accepted' => false])
            ->assertSessionHasErrors();

        $this->assertSame(0, FormSubmission::query()->count());
    }

    public function test_unknown_token_or_deleted_campaign_returns_404(): void
    {
        $world = $this->world();

        $this->get('/f/not-a-real-token')->assertNotFound();

        $world['campaign']->delete();
        $this->get('/f/'.$world['location']->public_token)->assertNotFound();
        $this->submitPublicEntry($world['location'])->assertNotFound();
    }

    public function test_honeypot_submissions_are_silently_dropped(): void
    {
        $world = $this->world();

        $this->post('/f/'.$world['location']->public_token, [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
            'website' => 'http://spam.example',
        ])->assertSessionHas('submitted', true);

        $this->assertSame(0, FormSubmission::query()->count());
    }

    public function test_submissions_are_rate_limited_per_ip_and_link(): void
    {
        $world = $this->world();

        for ($i = 0; $i < 10; $i++) {
            $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors();
        }
        $this->submitPublicEntry($world['location'])->assertTooManyRequests();

        $this->assertSame(10, FormSubmission::query()->count());
    }

    public function test_entries_stay_within_their_client(): void
    {
        $world = $this->world();
        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors();
        $entry = FormSubmission::query()->sole();

        $this->actingAs($world['userA'])->get('/submissions/'.$entry->id)->assertOk();
        $this->actingAs($world['userB'])->get('/submissions/'.$entry->id)->assertForbidden();
    }
}
