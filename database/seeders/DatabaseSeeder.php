<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $eo = User::factory()->superadmin()->create([
            'name' => 'Superadmin EO',
            'email' => 'eo@eo.test',
        ]);

        $mayora = Client::query()->create([
            'name' => 'Mayora',
            'code' => 'MAYORA',
            'notes' => 'Client contoh form sampling Kopi Lepas Haus.',
        ]);

        $imoby = Client::query()->create([
            'name' => 'IMOBY',
            'code' => 'IMOBY',
            'notes' => 'Client kedua untuk tes isolasi data.',
        ]);

        User::factory()->clientUser($mayora)->create([
            'name' => 'Client Mayora',
            'username' => 'mayora',
        ]);

        User::factory()->clientUser($imoby)->create([
            'name' => 'Client IMOBY',
            'username' => 'imoby',
        ]);

        $spg1 = User::factory()->spg()->create([
            'name' => 'Siti SPG',
            'username' => 'spg1',
        ]);

        User::factory()->spg()->create([
            'name' => 'Budi SPG',
            'username' => 'spg2',
        ]);

        $product = Product::query()->create([
            'client_id' => $mayora->id,
            'name' => 'Kopi Lepas Haus',
            'sku' => 'KLH-01',
        ]);

        Product::query()->create([
            'client_id' => $imoby->id,
            'name' => 'IMOBY Core',
            'sku' => 'IMB-01',
        ]);

        $campaign = Campaign::query()->create([
            'client_id' => $mayora->id,
            'name' => 'Sampling Kopi Lepas Haus',
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays(14)->toDateString(),
            'status' => CampaignStatus::Active,
            'mask_identity_for_client' => true,
        ]);
        $campaign->products()->attach($product);

        $location = CampaignLocation::query()->create([
            'campaign_id' => $campaign->id,
            'name' => 'PIK Avenue',
            'area' => 'Jakarta Utara',
            'address' => 'PIK Avenue',
        ]);

        $form = FormTemplate::query()->create([
            'campaign_id' => $campaign->id,
            'name' => 'Form Sampling Kopi Lepas Haus',
            'version' => 1,
            'status' => FormStatus::Published,
            'requires_consent' => true,
            'consent_text' => 'Saya setuju data ini dipakai untuk keperluan campaign sampling.',
            'qualification_enabled' => true,
            'qualification_rule' => ['type' => 'all_yes_no_true'],
        ]);

        foreach (MayoraFormDefinition::fields() as $field) {
            $form->fields()->create($field);
        }

        Assignment::query()->create([
            'campaign_id' => $campaign->id,
            'location_id' => $location->id,
            'form_template_id' => $form->id,
            'user_id' => $spg1->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(8),
            'status' => AssignmentStatus::Active,
        ]);

        $eo->forceFill(['email_verified_at' => now()])->save();
    }
}
