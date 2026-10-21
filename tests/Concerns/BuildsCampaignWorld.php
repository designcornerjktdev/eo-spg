<?php

namespace Tests\Concerns;

use App\Domain\Forms\MayoraFormDefinition;
use App\Enums\AssignmentStatus;
use App\Enums\CampaignStatus;
use App\Enums\FormStatus;
use App\Models\Assignment;
use App\Models\Campaign;
use App\Models\CampaignLocation;
use App\Models\Client;
use App\Models\FormTemplate;
use App\Models\Product;
use App\Models\User;

trait BuildsCampaignWorld
{
    /**
     * @return array{
     *     eo: User,
     *     clientA: Client,
     *     clientB: Client,
     *     userA: User,
     *     userB: User,
     *     spg: User,
     *     otherSpg: User,
     *     product: Product,
     *     campaign: Campaign,
     *     otherCampaign: Campaign,
     *     location: CampaignLocation,
     *     form: FormTemplate,
     *     assignment: Assignment
     * }
     */
    protected function world(): array
    {
        $eo = User::factory()->superadmin()->create();
        $clientA = Client::factory()->create(['name' => 'Mayora']);
        $clientB = Client::factory()->create(['name' => 'IMOBY']);
        $userA = User::factory()->clientUser($clientA)->create();
        $userB = User::factory()->clientUser($clientB)->create();
        $spg = User::factory()->spg()->create();
        $otherSpg = User::factory()->spg()->create();

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
            'consent_text' => 'Saya setuju data dipakai untuk campaign.',
            'qualification_enabled' => true,
            'qualification_rule' => ['type' => 'all_yes_no_true'],
        ]);

        foreach (MayoraFormDefinition::fields() as $field) {
            $form->fields()->create($field);
        }

        $assignment = Assignment::factory()->create([
            'campaign_id' => $campaign->id,
            'location_id' => $location->id,
            'form_template_id' => $form->id,
            'user_id' => $spg->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(6),
            'status' => AssignmentStatus::Active,
        ]);

        return compact(
            'eo',
            'clientA',
            'clientB',
            'userA',
            'userB',
            'spg',
            'otherSpg',
            'product',
            'campaign',
            'otherCampaign',
            'location',
            'form',
            'assignment',
        );
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
