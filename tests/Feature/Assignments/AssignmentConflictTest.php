<?php

namespace Tests\Feature\Assignments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class AssignmentConflictTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_overlapping_assignment_is_rejected(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->post('/assignments', [
            'campaign_id' => $world['campaign']->id,
            'location_id' => $world['location']->id,
            'form_template_id' => $world['form']->id,
            'user_id' => $world['spg']->id,
            'starts_at' => now()->addHour()->toDateTimeString(),
            'ends_at' => now()->addHours(3)->toDateTimeString(),
        ])->assertSessionHasErrors('starts_at');
    }

    public function test_spg_cannot_view_another_assignment(): void
    {
        $world = $this->world();

        $this->actingAs($world['otherSpg'])
            ->get('/tasks/'.$world['assignment']->id.'/fill')
            ->assertForbidden();
    }
}
