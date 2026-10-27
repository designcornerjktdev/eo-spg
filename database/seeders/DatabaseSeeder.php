<?php

namespace Database\Seeders;

use App\Domain\Forms\MayoraFormDefinition;
use App\Domain\Submissions\Services\SubmissionRecorder;
use App\Domain\Submissions\Services\SubmissionVoider;
use App\Enums\CampaignStatus;
use App\Enums\FieldType;
use App\Enums\FormStatus;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /** Days of past field work to generate entries for (today excluded). */
    private const PAST_DAYS = 13;

    private const JOBS = ['Karyawan swasta', 'Mahasiswa', 'Wiraswasta', 'Ibu rumah tangga', 'Freelancer', 'PNS'];

    /** Weighted so the dashboard charts show a clear leader. */
    private const SOURCES = ['Instagram' => 5, 'TikTok' => 4, 'Lewat booth' => 3, 'Teman' => 2];

    private const FLAVOURS = ['Original', 'Caramel', 'Hazelnut', 'Less sugar'];

    /** Colours for the generated demo photos. */
    private const PHOTO_COLOURS = [[37, 99, 235], [22, 163, 74], [217, 119, 6], [219, 39, 119], [124, 58, 237]];

    public function run(SubmissionRecorder $recorder, SubmissionVoider $voider): void
    {
        // Deterministic demo data: the same seed always produces the same dashboard.
        mt_srand(2026);
        fake()->seed(2026);

        // Photos from a previous seed would be orphaned by migrate:fresh.
        Storage::disk(SubmissionRecorder::IMAGE_DISK)->deleteDirectory('submissions');

        $eo = User::factory()->superadmin()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@mail.com',
        ]);

        // Operations admin: runs campaigns, forms and entries; signs in with email or username.
        User::factory()->adminUser()->create(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@mail.com']);

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

        $fields = MayoraFormDefinition::fields();
        foreach ($fields as $field) {
            $form->fields()->create($field);
        }

        // Choice questions feed the dashboard answer charts; the photo field shows image upload and Excel thumbnails.
        $extra = [
            ['sumber_info', 'Tahu acara ini dari mana?', FieldType::SingleChoice, true, true, array_keys(self::SOURCES)],
            ['varian_favorit', 'Varian yang ingin dicoba', FieldType::MultipleChoice, false, true, self::FLAVOURS],
            ['foto_dokumentasi', 'Foto dokumentasi', FieldType::Image, false, false, null],
        ];
        foreach ($extra as $index => [$key, $label, $type, $required, $dashboard, $options]) {
            $form->fields()->create([
                'key' => $key,
                'label' => $label,
                'type' => $type->value,
                'required' => $required,
                'sort_order' => count($fields) + $index + 1,
                'visible_to_client' => true,
                'is_identity' => false,
                'show_on_dashboard' => $dashboard,
                'options' => $options,
            ]);
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
            'sumber_info' => $this->weighted(self::SOURCES),
            'varian_favorit' => collect(self::FLAVOURS)->filter(fn () => mt_rand(1, 10) <= 4)->values()->all(),
            // Most booths snap a photo, but not every visitor agrees to one.
            'foto_dokumentasi' => mt_rand(1, 10) <= 7 ? $this->photo() : null,
        ];
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function weighted(array $weights): string
    {
        $pick = mt_rand(1, array_sum($weights));
        foreach ($weights as $value => $weight) {
            if (($pick -= $weight) <= 0) {
                return $value;
            }
        }

        return array_key_first($weights);
    }

    /**
     * A small generated JPEG standing in for a booth photo; the recorder converts it to WebP like a real upload.
     */
    private function photo(): UploadedFile
    {
        [$r, $g, $b] = self::PHOTO_COLOURS[array_rand(self::PHOTO_COLOURS)];
        $image = imagecreatetruecolor(640, 480);
        imagefilledrectangle($image, 0, 0, 640, 480, imagecolorallocate($image, $r, $g, $b));
        $light = imagecolorallocatealpha($image, 255, 255, 255, 80);
        imagefilledellipse($image, mt_rand(120, 520), mt_rand(120, 360), 260, 260, $light);
        imagefilledrectangle($image, 0, 400, 640, 480, imagecolorallocatealpha($image, 0, 0, 0, 60));
        imagestring($image, 5, 20, 430, 'Demo photo '.now()->format('d M H:i'), imagecolorallocate($image, 255, 255, 255));

        $path = tempnam(sys_get_temp_dir(), 'seed').'.jpg';
        imagejpeg($image, $path, 85);
        imagedestroy($image);

        return new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);
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
