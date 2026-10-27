<?php

namespace Tests\Concerns;

use App\Domain\Forms\MayoraFormDefinition;
use App\Enums\CampaignStatus;
use App\Enums\FormStatus;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;

trait BuildsCampaignWorld
{
    /**
     * @return array{
     *     eo: User,
     *     userA: User,
     *     userB: User,
     *     campaign: Campaign,
     *     otherCampaign: Campaign,
     *     location: CampaignLocation,
     *     form: FormTemplate,
     *     otherLocation: CampaignLocation
     * }
     */
    protected function world(): array
    {
        $eo = User::factory()->superadmin()->create();
        // userA may view the Mayora campaign only, userB the IMOBY campaign only.
        $userA = User::factory()->clientUser()->create();
        $userB = User::factory()->clientUser()->create();

        $campaign = Campaign::factory()->create([
            'name' => 'Sampling Kopi Lepas Haus',
            'status' => CampaignStatus::Active,
        ]);
        $campaign->viewers()->attach($userA);

        $otherCampaign = Campaign::factory()->create([
            'name' => 'Campaign IMOBY',
            'status' => CampaignStatus::Active,
        ]);
        $otherCampaign->viewers()->attach($userB);

        $location = CampaignLocation::factory()->create([
            'campaign_id' => $campaign->id,
            'name' => 'PIK Avenue',
            'area' => 'Jakarta Utara',
        ]);

        $form = FormTemplate::factory()->create([
            'campaign_id' => $campaign->id,
            'name' => 'Form Sampling Kopi Lepas Haus',
            'status' => FormStatus::Published,
            'requires_consent' => true,
            'consent_text' => 'I agree my data may be used for this campaign.',
            'qualification_enabled' => true,
            'qualification_rule' => ['type' => 'all_yes_no_true'],
        ]);

        foreach (MayoraFormDefinition::fields() as $field) {
            $form->fields()->create($field);
        }

        // The other campaign gets its own published form so its public link is open too.
        $otherForm = FormTemplate::factory()->create([
            'campaign_id' => $otherCampaign->id,
            'name' => 'Form IMOBY',
            'status' => FormStatus::Published,
            'requires_consent' => true,
            'consent_text' => 'I agree my data may be used for this campaign.',
        ]);

        foreach (MayoraFormDefinition::fields() as $field) {
            $otherForm->fields()->create($field);
        }

        // A location of the other client's campaign, to check that entries never cross clients.
        $otherLocation = CampaignLocation::factory()->create([
            'campaign_id' => $otherCampaign->id,
            'name' => 'Pakuwon Mall',
            'area' => 'Surabaya',
        ]);

        return compact(
            'eo',
            'userA',
            'userB',
            'campaign',
            'otherCampaign',
            'location',
            'form',
            'otherLocation',
        );
    }

    /**
     * Submits one valid public entry to a location's link, as a consumer (no login).
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function submitPublicEntry(CampaignLocation $location, array $overrides = []): TestResponse
    {
        return $this->post('/f/'.$location->public_token, [
            'answers' => [...$this->validAnswers(), ...$overrides],
            'consent_accepted' => true,
        ]);
    }

    /**
     * All cell values of a downloaded Excel export, joined into one string for contains-checks.
     */
    protected function exportedText(TestResponse $response): string
    {
        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($file, $response->streamedContent());
        $rows = IOFactory::load($file)->getActiveSheet()->toArray();
        unlink($file);

        return implode("\n", array_map(fn ($row) => implode(',', $row), $rows));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validAnswers(): array
    {
        return [
            'nama' => 'Siti Aminah',
            'usia' => 24,
            'no_handphone' => '081234567890',
            'pekerjaan' => 'Karyawan',
            'domisili' => 'Bandung',
            'akun_instagram' => 'sitiaminah',
            'kunjungi_poi' => true,
            'aktivitas_area' => true,
            'konsumsi_csd' => true,
        ];
    }
}
