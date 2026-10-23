<?php

namespace Tests\Feature\Campaigns;

use App\Enums\CampaignStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_end_date_cannot_precede_start_date(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->post('/campaigns', [
            'name' => 'Campaign Salah',
            'starts_on' => '2026-10-20',
            'ends_on' => '2026-10-19',
            'locations' => [['name' => 'Mall', 'area' => 'Jakarta', 'address' => 'Jl. Sudirman']],
        ])->assertSessionHasErrors('ends_on');
    }

    public function test_campaign_starts_as_draft(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->post('/campaigns', [
            'name' => 'Campaign Baru',
            'starts_on' => '2026-10-20',
            'ends_on' => '2026-10-25',
            'locations' => [['name' => 'Mall', 'area' => 'Jakarta', 'address' => 'Jl. Sudirman']],
        ])->assertRedirect();

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Campaign Baru',
            'status' => CampaignStatus::Draft->value,
        ]);
    }
}
