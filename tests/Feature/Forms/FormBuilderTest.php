<?php

namespace Tests\Feature\Forms;

use App\Domain\Forms\MayoraFormDefinition;
use App\Enums\FormStatus;
use App\Models\FormTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_mayora_form_can_be_created(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->post('/forms', [
            'campaign_id' => $world['campaign']->id,
            'name' => 'Form Mayora 2',
            'fields' => MayoraFormDefinition::fields(),
        ])->assertRedirect();

        $form = FormTemplate::query()->where('name', 'Form Mayora 2')->first();
        $this->assertNotNull($form);
        $this->assertCount(9, $form->fields);
    }

    public function test_form_is_locked_after_first_submission(): void
    {
        $world = $this->world();

        $this->actingAs($world['spg'])->post('/tugas/'.$world['assignment']->id.'/isi', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ])->assertRedirect();

        $this->assertSame(FormStatus::Locked, $world['form']->fresh()->status);

        $this->actingAs($world['eo'])->put('/forms/'.$world['form']->id, [
            'campaign_id' => $world['campaign']->id,
            'name' => 'Tidak boleh diubah',
            'fields' => MayoraFormDefinition::fields(),
        ])->assertSessionHasErrors('form');
    }
}
