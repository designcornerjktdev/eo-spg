<?php

namespace Tests\Concerns;

use App\Domain\Forms\MayoraFormDefinition;
use App\Enums\CampaignStatus;
use App\Enums\FormStatus;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\Client;
use App\Models\FormTemplate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Testing\TestResponse;

trait BuildsCampaignWorld
{
    /**
     * @return array{
     *     eo: User,
     *     clientA: Client,
     *     clientB: Client,
     *     userA: User,
     *     userB: User,
     *     product: Product,
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
        $clientA = Client::factory()->create(['name' => 'Mayora']);
        $clientB = Client::factory()->create(['name' => 'IMOBY']);
        $userA = User::factory()->clientUser($clientA)->create();
        $userB = User::factory()->clientUser($clientB)->create();

        $product = Product::factory()->create(['client_id' => $clientA->id, 'name' => 'Kopi Lepas Haus']);
        $campaign = Campaign::factory()->create([
            'client_id' => $clientA->id,
            'name' => 'Sampling Kopi Lepas Haus',
            'status' => CampaignStatus::Active,
            'mask_identity_for_client' => true,
        ]);
        $campaign->products()->attach($product);

        $otherCampaign = Campaign::factory()->create([
            'client_id' => $clientB->id,
            'name' => 'Campaign IMOBY',
            'status' => CampaignStatus::Active,
        ]);

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

        // A location of the other client's campaign, to check that entries never cross clients.
        $otherLocation = CampaignLocation::factory()->create([
            'campaign_id' => $otherCampaign->id,
            'name' => 'Pakuwon Mall',
            'area' => 'Surabaya',
        ]);

        return compact(
            'eo',
            'clientA',
            'clientB',
            'userA',
            'userB',
            'product',
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
