<?php

namespace Database\Seeders;

use App\Domain\Forms\MayoraFormDefinition;
use App\Domain\Submissions\Services\SubmissionRecorder;
use App\Domain\Submissions\Services\SubmissionVoider;
use App\Enums\CampaignStatus;
use App\Enums\FormStatus;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /** Days of past field work to generate entries for (today excluded). */
    private const PAST_DAYS = 13;

    private const JOBS = ['Karyawan swasta', 'Mahasiswa', 'Wiraswasta', 'Ibu rumah tangga', 'Freelancer', 'PNS'];

    public function run(SubmissionRecorder $recorder, SubmissionVoider $voider): void
    {
        // Deterministic demo data: the same seed always produces the same dashboard.
        mt_srand(2026);
        fake()->seed(2026);

        $eo = User::factory()->superadmin()->create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
        ]);

        // Client accounts are read-only and only see the campaigns they are granted below.
        $mayora = User::factory()->clientUser()->create(['name' => 'Client Mayora', 'username' => 'mayora']);
        $imoby = User::factory()->clientUser()->create(['name' => 'Client IMOBY', 'username' => 'imoby']);

        // Mayora: three Jakarta/Bandung locations, running for two weeks already.
        [$mayoraCampaign, , $mayoraLocations] = $this->campaign(
            'Sampling Kopi Lepas Haus',
            'Form Sampling Kopi Lepas Haus',
            [
                ['PIK Avenue', 'Jakarta Utara', 'Jl. Pantai Indah Kapuk Boulevard', -6.1087, 106.7398],
                ['Grand Indonesia', 'Jakarta Pusat', 'Jl. M.H. Thamrin No.1', -6.1950, 106.8205],
                ['Paris Van Java', 'Bandung', 'Jl. Sukajadi No.131-139', -6.8892, 107.5960],
            ],
        );

        // IMOBY: one Surabaya location, started a week ago — a second client account keeps access isolation visible.
        [$imobyCampaign, , $imobyLocations] = $this->campaign(
            'IMOBY Core Roadshow',
            'Form IMOBY Core Roadshow',
            [['Pakuwon Mall', 'Surabaya', 'Jl. Puncak Indah Lontar No.2', -7.2894, 112.6764]],
            startedDaysAgo: 6,
        );

        $mayoraCampaign->viewers()->attach($mayora);
        $imobyCampaign->viewers()->attach($imoby);

        $locations = [
            [$mayoraLocations[0], self::PAST_DAYS],
            [$mayoraLocations[1], self::PAST_DAYS],
            [$mayoraLocations[2], self::PAST_DAYS],
            [$imobyLocations[0], 6],
        ];

        foreach ($locations as [$location, $days]) {
            for ($daysAgo = $days; $daysAgo >= 1; $daysAgo--) {
                // Weekends are busier, and the odd quiet day makes the chart less uniform.
                $day = now()->subDays($daysAgo)->startOfDay();
                if (mt_rand(1, 10) === 1) {
                    continue;
                }
                $entries = $day->isWeekend() ? mt_rand(9, 16) : mt_rand(3, 9);
                $this->collectDay($recorder, $voider, $eo, $location, $day, $entries);
            }
        }

        // A few responses already came in this morning at PIK Avenue.
        $this->recordEntries($recorder, $mayoraLocations[0], now()->subHours(2), now()->subMinutes(5), 3);

        $eo->forceFill(['email_verified_at' => now()])->save();
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: float, 4: float}>  $locations
     * @return array{0: Campaign, 1: FormTemplate, 2: array<int, CampaignLocation>}
     */
    private function campaign(string $name, string $formName, array $locations, int $startedDaysAgo = self::PAST_DAYS): array
    {
        $campaign = Campaign::query()->create([
            'name' => $name,
            'starts_on' => now()->subDays($startedDaysAgo)->toDateString(),
            'ends_on' => now()->addDays(14)->toDateString(),
            'status' => CampaignStatus::Active,
            'mask_identity_for_client' => true,
        ]);

        $models = collect($locations)->map(fn (array $location) => CampaignLocation::query()->create([
            'campaign_id' => $campaign->id,
            'name' => $location[0],
            'area' => $location[1],
            'address' => $location[2],
            'latitude' => $location[3],
            'longitude' => $location[4],
        ]))->all();

        $form = FormTemplate::query()->create([
            'campaign_id' => $campaign->id,
            'name' => $formName,
            'version' => 1,
            'status' => FormStatus::Published,
            'requires_consent' => true,
            'consent_text' => 'I agree that my data may be used for this sampling campaign.',
            'qualification_enabled' => true,
            'qualification_rule' => ['type' => 'all_yes_no_true'],
        ]);

        foreach (MayoraFormDefinition::fields() as $field) {
            $form->fields()->create($field);
        }

        return [$campaign, $form, $models];
    }

    private function collectDay(SubmissionRecorder $recorder, SubmissionVoider $voider, User $eo, CampaignLocation $location, Carbon $day, int $entries): void
    {
        // Booth hours 10:00-18:00.
        $submissions = $this->recordEntries($recorder, $location, $day->copy()->setTime(10, 0), $day->copy()->setTime(18, 0), $entries);

        // About 1 in 20 entries is voided by the EO, as happens with real duplicates.
        foreach ($submissions as $submission) {
            if (mt_rand(1, 20) === 1) {
                Carbon::setTestNow($submission->submitted_at->copy()->addHours(2));
                $voider->void($submission, $eo, 'Duplicate entry');
            }
        }
        Carbon::setTestNow();
    }

    /**
     * Records entries through the real SubmissionRecorder (qualification, dashboard values, form lock)
     * by moving "now" to each entry's time inside the booth hours.
     *
     * @return array<int, FormSubmission>
     */
    private function recordEntries(SubmissionRecorder $recorder, CampaignLocation $location, Carbon $from, Carbon $to, int $count): array
    {
        $times = collect(range(1, $count))
            ->map(fn () => $from->copy()->addSeconds(mt_rand(60, max(61, $from->diffInSeconds($to) - 60))))
            ->sort()
            ->values();

        $submissions = [];
        foreach ($times as $time) {
            Carbon::setTestNow($time);
            $submissions[] = $recorder->record($location, $this->answers(), true, $this->positionNear($location));
        }
        Carbon::setTestNow();

        return $submissions;
    }

    /**
     * @return array<string, mixed>
     */
    private function answers(): array
    {
        $name = fake('id_ID')->name();

        return [
            'nama' => $name,
            'usia' => mt_rand(18, 45),
            'no_handphone' => '08'.mt_rand(11, 99).mt_rand(1000000, 9999999),
            'pekerjaan' => self::JOBS[array_rand(self::JOBS)],
            'domisili' => fake('id_ID')->city(),
            'akun_instagram' => mt_rand(1, 4) === 1 ? '' : strtolower(str_replace([' ', '.'], '', $name)).mt_rand(1, 99),
            // ~80% yes per question, so roughly half of all entries qualify.
            'kunjungi_poi' => mt_rand(1, 10) <= 8,
            'aktivitas_area' => mt_rand(1, 10) <= 8,
            'konsumsi_csd' => mt_rand(1, 10) <= 8,
        ];
    }

    /**
     * A phone position up to ~150 m from the location pin, so seeded entries pass the geofence.
     *
     * @return array{latitude: float, longitude: float, accuracy: float}
     */
    private function positionNear(CampaignLocation $location): array
    {
        $meters = mt_rand(0, 150);
        $angle = deg2rad(mt_rand(0, 359));

        return [
            'latitude' => $location->latitude + ($meters * cos($angle)) / 111320,
            'longitude' => $location->longitude + ($meters * sin($angle)) / (111320 * cos(deg2rad($location->latitude))),
            'accuracy' => (float) mt_rand(5, 40),
        ];
    }
}
