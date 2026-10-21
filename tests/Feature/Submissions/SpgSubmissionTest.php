<?php

namespace Tests\Feature\Submissions;

use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class SpgSubmissionTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_spg_can_submit_row_with_server_timestamp(): void
    {
        $world = $this->world();

        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/submissions', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ])->assertRedirect();

        $submission = FormSubmission::query()->first();
        $this->assertNotNull($submission);
        $this->assertSame($world['spg']->id, $submission->submitted_by);
        $this->assertNotNull($submission->submitted_at);
        $this->assertTrue($submission->qualified);
        $this->assertSame('Saya setuju data dipakai untuk campaign.', $submission->consent_text_version);
    }

    public function test_assignment_page_lists_own_submissions_and_store_returns_there(): void
    {
        $world = $this->world();
        $url = '/tasks/'.$world['assignment']->id.'/submissions';
        $index = '/tasks?assignment='.$world['assignment']->id;

        $this->actingAs($world['spg'])->post($url, [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ])->assertRedirect($index);

        $this->actingAs($world['spg'])->get($index)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('spg/index')
                ->has('submissions.data', 1)
                ->where('todayCount', 1));
    }

    public function test_required_fields_are_validated_on_server(): void
    {
        $world = $this->world();

        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/submissions', [
            'answers' => ['nama' => 'Siti'],
            'consent_accepted' => true,
        ])->assertSessionHasErrors();
    }

    public function test_spg_only_sees_own_submissions_and_cannot_edit(): void
    {
        $world = $this->world();

        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/submissions', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ]);

        $submission = FormSubmission::query()->first();

        $this->actingAs($world['otherSpg'])->get('/tasks')->assertOk()->assertDontSee('Sampling Kopi Lepas Haus');
        $this->actingAs($world['spg'])->put('/submissions/'.$submission->id, [
            'answers' => ['nama' => 'Diubah'],
        ])->assertMethodNotAllowed();
    }
}
