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

    public function test_overlap_is_rejected_with_datetime_local_input_format(): void
    {
        $world = $this->world();
        $existing = $world['assignment'];

        // Browser datetime-local input uses a "T" separator and no seconds.
        $this->actingAs($world['eo'])->post('/assignments', [
            'campaign_id' => $world['campaign']->id,
            'location_id' => $world['location']->id,
            'form_template_id' => $world['form']->id,
            'user_id' => $world['spg']->id,
            'starts_at' => $existing->starts_at->copy()->addMinutes(30)->format('Y-m-d\TH:i'),
            'ends_at' => $existing->ends_at->copy()->addHours(2)->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('starts_at');

        $this->assertSame(1, $world['spg']->assignments()->count());
    }

    public function test_spg_cannot_view_another_assignment(): void
    {
        $world = $this->world();

        $this->actingAs($world['otherSpg'])
            ->get('/tasks/'.$world['assignment']->id)
            ->assertForbidden();
    }
}
