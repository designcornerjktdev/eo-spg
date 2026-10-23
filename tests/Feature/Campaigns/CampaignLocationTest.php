<?php

namespace Tests\Feature\Campaigns;

use App\Models\CampaignLocation;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class CampaignLocationTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    private function payload(array $world, array $locations): array
    {
        return [
            'name' => $world['campaign']->name,
            'starts_on' => $world['campaign']->starts_on->toDateString(),
            'ends_on' => $world['campaign']->ends_on->toDateString(),
            'locations' => $locations,
        ];
    }

    public function test_location_coordinates_from_map_are_saved(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->put('/campaigns/'.$world['campaign']->id, $this->payload($world, [
            ['id' => $world['location']->id, 'name' => 'PIK Avenue', 'area' => 'Jakarta Utara', 'address' => 'Jl. Pantai Indah Kapuk', 'latitude' => -6.1087, 'longitude' => 106.7398],
        ]))->assertSessionHasNoErrors();

        $location = $world['location']->refresh();
        $this->assertEqualsWithDelta(-6.1087, $location->latitude, 0.00001);
        $this->assertEqualsWithDelta(106.7398, $location->longitude, 0.00001);
    }

    public function test_coordinates_must_be_valid_and_paired(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->put('/campaigns/'.$world['campaign']->id, $this->payload($world, [
            ['id' => $world['location']->id, 'name' => 'X', 'latitude' => 120, 'longitude' => 106.7],
        ]))->assertSessionHasErrors('locations.0.latitude');

        $this->actingAs($world['eo'])->put('/campaigns/'.$world['campaign']->id, $this->payload($world, [
            ['id' => $world['location']->id, 'name' => 'X', 'latitude' => -6.1],
        ]))->assertSessionHasErrors('locations.0.longitude');
    }

    public function test_location_with_entries_cannot_be_removed(): void
    {
        $world = $this->world();
        $this->submitPublicEntry($world['location'])->assertSessionHasNoErrors();

        // Replacing the location list without the old location that has entries must be rejected.
        $this->actingAs($world['eo'])->put('/campaigns/'.$world['campaign']->id, $this->payload($world, [
            ['name' => 'New Location', 'area' => 'Bandung'],
        ]))->assertSessionHasErrors('locations');

        $this->assertModelExists($world['location']);
        $this->assertSame(1, FormSubmission::query()->where('location_id', $world['location']->id)->count());
    }

    public function test_unused_location_can_be_removed(): void
    {
        $world = $this->world();
        $unused = CampaignLocation::factory()->create(['campaign_id' => $world['campaign']->id, 'name' => 'Unused']);

        $this->actingAs($world['eo'])->put('/campaigns/'.$world['campaign']->id, $this->payload($world, [
            ['id' => $world['location']->id, 'name' => $world['location']->name],
        ]))->assertSessionHasNoErrors();

        $this->assertModelMissing($unused);
        $this->assertModelExists($world['location']);
    }
}
