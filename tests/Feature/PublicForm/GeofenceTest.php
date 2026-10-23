<?php

namespace Tests\Feature\PublicForm;

use App\Domain\Submissions\Services\Geofence;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class GeofenceTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    // PIK Avenue, Jakarta.
    private const PIN = ['latitude' => -6.1087, 'longitude' => 106.7398];

    private function pinnedWorld(int $radius = 300): array
    {
        $world = $this->world();
        $world['location']->update([...self::PIN, 'radius_meters' => $radius]);

        return $world;
    }

    /**
     * A point $meters north of the pin.
     *
     * @return array{latitude: float, longitude: float, accuracy: float}
     */
    private function northOfPin(int $meters, float $accuracy = 10): array
    {
        return ['latitude' => self::PIN['latitude'] + $meters / 111320, 'longitude' => self::PIN['longitude'], 'accuracy' => $accuracy];
    }

    private function submitFrom(array $world, ?array $position)
    {
        return $this->post('/f/'.$world['location']->public_token, [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
            'position' => $position,
        ]);
    }

    public function test_page_tells_the_browser_to_check_location_only_for_pinned_locations(): void
    {
        $world = $this->world();
        $this->get('/f/'.$world['location']->public_token)->assertInertia(fn ($page) => $page->where('geofence', null));

        $world['location']->update([...self::PIN, 'radius_meters' => 250]);
        $this->get('/f/'.$world['location']->public_token)->assertInertia(fn ($page) => $page->where('geofence.radius', 250));
    }

    public function test_entry_inside_the_radius_is_accepted_and_audited(): void
    {
        $world = $this->pinnedWorld();

        $this->submitFrom($world, $this->northOfPin(120, 15))->assertSessionHasNoErrors();

        $entry = FormSubmission::query()->sole();
        $this->assertEqualsWithDelta(120, $entry->distance_meters, 2);
        $this->assertSame(15, $entry->location_accuracy_meters);
        $this->assertNotNull($entry->latitude);
    }

    public function test_entry_outside_the_radius_is_rejected(): void
    {
        $world = $this->pinnedWorld();

        $this->submitFrom($world, $this->northOfPin(2000))
            ->assertSessionHasErrors(['position' => 'You need to be at PIK Avenue to submit this form. You appear to be 2 km away.']);

        $this->assertSame(0, FormSubmission::query()->count());
    }

    public function test_entry_without_a_position_is_rejected_for_pinned_locations(): void
    {
        $world = $this->pinnedWorld();

        $this->submitFrom($world, null)->assertSessionHasErrors('position');
        $this->assertSame(0, FormSubmission::query()->count());
    }

    public function test_gps_accuracy_is_credited_but_capped(): void
    {
        $world = $this->pinnedWorld(300);

        // 380 m away with ±100 m accuracy: within 300 m once accuracy is credited.
        $this->submitFrom($world, $this->northOfPin(380, 100))->assertSessionHasNoErrors();

        // A huge accuracy value must not stretch the fence beyond the 100 m credit.
        $this->submitFrom($world, $this->northOfPin(600, 5000))->assertSessionHasErrors('position');

        $this->assertSame(1, FormSubmission::query()->count());
    }

    public function test_locations_without_a_map_point_accept_entries_from_anywhere(): void
    {
        $world = $this->world();

        $this->submitFrom($world, null)->assertSessionHasNoErrors();
        $this->assertNull(FormSubmission::query()->sole()->distance_meters);
    }

    public function test_radius_is_configurable_per_location(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->put('/campaigns/'.$world['campaign']->id, [
            'name' => $world['campaign']->name,
            'starts_on' => $world['campaign']->starts_on->toDateString(),
            'ends_on' => $world['campaign']->ends_on->toDateString(),
            'locations' => [['id' => $world['location']->id, 'name' => 'PIK Avenue', ...self::PIN, 'radius_meters' => 20]],
        ])->assertSessionHasErrors('locations.0.radius_meters');

        $this->actingAs($world['eo'])->put('/campaigns/'.$world['campaign']->id, [
            'name' => $world['campaign']->name,
            'starts_on' => $world['campaign']->starts_on->toDateString(),
            'ends_on' => $world['campaign']->ends_on->toDateString(),
            'locations' => [['id' => $world['location']->id, 'name' => 'PIK Avenue', ...self::PIN, 'radius_meters' => 800]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(800, $world['location']->refresh()->radius_meters);
    }

    public function test_haversine_distance_is_accurate(): void
    {
        // PIK Avenue to Grand Indonesia is roughly 13 km in a straight line.
        $distance = app(Geofence::class)->distanceInMeters(-6.1087, 106.7398, -6.1950, 106.8205);

        $this->assertEqualsWithDelta(13050, $distance, 300);
    }
}
