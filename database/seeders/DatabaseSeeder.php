<?php

namespace Database\Seeders;

use App\Domain\Forms\MayoraFormDefinition;
use App\Domain\Submissions\Services\SubmissionRecorder;
use App\Domain\Submissions\Services\SubmissionVoider;
use App\Enums\AssignmentStatus;
use App\Enums\CampaignStatus;
use App\Enums\FormStatus;
use App\Models\Assignment;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\Client;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Product;
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
            'name' => 'EO Admin',
            'email' => 'eo@eo.test',
        ]);

        $mayora = Client::query()->create([
            'name' => 'Mayora',
            'code' => 'MAYORA',
            'notes' => 'Sample client for the Kopi Lepas Haus sampling form.',
        ]);

        $imoby = Client::query()->create([
            'name' => 'IMOBY',
            'code' => 'IMOBY',
            'notes' => 'Second client for data isolation testing.',
        ]);

        User::factory()->clientUser($mayora)->create(['name' => 'Client Mayora', 'username' => 'mayora']);
        User::factory()->clientUser($imoby)->create(['name' => 'Client IMOBY', 'username' => 'imoby']);

        $spgs = collect([
            ['Siti SPG', 'spg1'],
            ['Budi SPG', 'spg2'],
            ['Rina SPG', 'spg3'],
            ['Dewi SPG', 'spg4'],
        ])->map(fn (array $spg) => User::factory()->spg()->create(['name' => $spg[0], 'username' => $spg[1]]));

        $kopi = Product::query()->create(['client_id' => $mayora->id, 'name' => 'Kopi Lepas Haus', 'sku' => 'KLH-01']);
        $imobyCore = Product::query()->create(['client_id' => $imoby->id, 'name' => 'IMOBY Core', 'sku' => 'IMB-01']);

        // Mayora: three Jakarta/Bandung locations, running for two weeks already.
        [$mayoraCampaign, $mayoraForm, $mayoraLocations] = $this->campaign(
            $mayora,
            $kopi,
            'Sampling Kopi Lepas Haus',
            'Form Sampling Kopi Lepas Haus',
            [
                ['PIK Avenue', 'Jakarta Utara', 'Jl. Pantai Indah Kapuk Boulevard', -6.1087, 106.7398],
                ['Grand Indonesia', 'Jakarta Pusat', 'Jl. M.H. Thamrin No.1', -6.1950, 106.8205],
                ['Paris Van Java', 'Bandung', 'Jl. Sukajadi No.131-139', -6.8892, 107.5960],
            ],
        );

        // IMOBY: one Surabaya location, started a week ago — keeps client data isolation visible.
        [$imobyCampaign, $imobyForm, $imobyLocations] = $this->campaign(
            $imoby,
            $imobyCore,
            'IMOBY Core Roadshow',
            'Form IMOBY Core Roadshow',
            [['Pakuwon Mall', 'Surabaya', 'Jl. Puncak Indah Lontar No.2', -7.2894, 112.6764]],
            startedDaysAgo: 6,
        );

        // One SPG per location keeps every schedule overlap-free.
        $shifts = [
            [$mayoraCampaign, $mayoraForm, $mayoraLocations[0], $spgs[0], self::PAST_DAYS],
            [$mayoraCampaign, $mayoraForm, $mayoraLocations[1], $spgs[1], self::PAST_DAYS],
            [$mayoraCampaign, $mayoraForm, $mayoraLocations[2], $spgs[2], self::PAST_DAYS],
            [$imobyCampaign, $imobyForm, $imobyLocations[0], $spgs[3], 6],
        ];

        foreach ($shifts as [$campaign, $form, $location, $spg, $days]) {
            for ($daysAgo = $days; $daysAgo >= 1; $daysAgo--) {
                // Weekends are busier, and the odd day off makes the chart less uniform.
                $day = now()->subDays($daysAgo)->startOfDay();
                if (mt_rand(1, 10) === 1) {
                    continue;
                }
                $entries = $day->isWeekend() ? mt_rand(9, 16) : mt_rand(3, 9);
                $this->workShift($recorder, $voider, $eo, $campaign, $form, $location, $spg, $day, $entries);
            }
        }

        // Today: Siti is on shift right now (the SPG demo account), Budi starts later today.
        $today = Assignment::query()->create([
            'campaign_id' => $mayoraCampaign->id,
            'location_id' => $mayoraLocations[0]->id,
            'form_template_id' => $mayoraForm->id,
            'user_id' => $spgs[0]->id,
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->addHours(6),
            'status' => AssignmentStatus::Active,
        ]);
        $this->recordEntries($recorder, $today, $spgs[0], now()->subHours(2), now()->subMinutes(5), 3);

        Assignment::query()->create([
            'campaign_id' => $mayoraCampaign->id,
            'location_id' => $mayoraLocations[1]->id,
            'form_template_id' => $mayoraForm->id,
            'user_id' => $spgs[1]->id,
            'starts_at' => now()->addHours(7),
            'ends_at' => now()->addHours(12),
            'status' => AssignmentStatus::Scheduled,
        ]);

        // A cancelled shift so every assignment status shows up on the dashboard.
        Assignment::query()->create([
            'campaign_id' => $mayoraCampaign->id,
            'location_id' => $mayoraLocations[2]->id,
            'form_template_id' => $mayoraForm->id,
            'user_id' => $spgs[2]->id,
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'ends_at' => now()->addDays(2)->setTime(18, 0),
            'status' => AssignmentStatus::Cancelled,
        ]);

        $eo->forceFill(['email_verified_at' => now()])->save();
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: float, 4: float}>  $locations
     * @return array{0: Campaign, 1: FormTemplate, 2: array<int, CampaignLocation>}
     */
    private function campaign(Client $client, Product $product, string $name, string $formName, array $locations, int $startedDaysAgo = self::PAST_DAYS): array
    {
        $campaign = Campaign::query()->create([
            'client_id' => $client->id,
            'name' => $name,
            'starts_on' => now()->subDays($startedDaysAgo)->toDateString(),
            'ends_on' => now()->addDays(14)->toDateString(),
            'status' => CampaignStatus::Active,
            'mask_identity_for_client' => true,
        ]);
        $campaign->products()->attach($product);

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

    private function workShift(
        SubmissionRecorder $recorder,
        SubmissionVoider $voider,
        User $eo,
        Campaign $campaign,
        FormTemplate $form,
        CampaignLocation $location,
        User $spg,
        Carbon $day,
        int $entries,
    ): void {
        $assignment = Assignment::query()->create([
            'campaign_id' => $campaign->id,
            'location_id' => $location->id,
            'form_template_id' => $form->id,
            'user_id' => $spg->id,
            'starts_at' => $day->copy()->setTime(10, 0),
            'ends_at' => $day->copy()->setTime(18, 0),
            'status' => AssignmentStatus::Active,
        ]);

        $submissions = $this->recordEntries($recorder, $assignment, $spg, $assignment->starts_at, $assignment->ends_at, $entries);

        // About 1 in 20 entries is voided by the EO, as happens with real duplicates.
        foreach ($submissions as $submission) {
            if (mt_rand(1, 20) === 1) {
                Carbon::setTestNow($submission->submitted_at->copy()->addHours(2));
                $voider->void($submission, $eo, 'Duplicate entry');
            }
        }
        Carbon::setTestNow();

        $assignment->update(['status' => AssignmentStatus::Done]);
    }

    /**
     * Records entries through the real SubmissionRecorder (qualification, dashboard values, form lock)
     * by moving "now" to each entry's time inside the shift.
     *
     * @return array<int, FormSubmission>
     */
    private function recordEntries(SubmissionRecorder $recorder, Assignment $assignment, User $spg, Carbon $from, Carbon $to, int $count): array
    {
        $times = collect(range(1, $count))
            ->map(fn () => $from->copy()->addSeconds(mt_rand(60, max(61, $from->diffInSeconds($to) - 60))))
            ->sort()
            ->values();

        $submissions = [];
        foreach ($times as $time) {
            Carbon::setTestNow($time);
            $submissions[] = $recorder->record($assignment, $spg, $this->answers(), true);
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
}
