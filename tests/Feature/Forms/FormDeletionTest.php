<?php

namespace Tests\Feature\Forms;

use App\Enums\FormStatus;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\FormTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class FormDeletionTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_unused_draft_form_can_be_deleted(): void
    {
        $world = $this->world();
        $draft = FormTemplate::factory()->create(['campaign_id' => $world['campaign']->id, 'status' => FormStatus::Draft]);

        $this->actingAs($world['eo'])->get('/forms/'.$draft->id.'/edit')
            ->assertInertia(fn ($page) => $page->where('deleteBlocker', null));
        $this->actingAs($world['eo'])->delete('/forms/'.$draft->id)->assertRedirect('/forms');

        $this->assertModelMissing($draft);
        $this->assertTrue(AuditLog::query()->where('action', 'form.deleted')->exists());
    }

    public function test_published_form_cannot_be_deleted(): void
    {
        $world = $this->world();
        $this->assertNotSame(FormStatus::Draft, $world['form']->status);

        $this->actingAs($world['eo'])->delete('/forms/'.$world['form']->id)->assertSessionHasErrors('form');

        $this->assertModelExists($world['form']);
        $this->assertModelExists($world['assignment']);
    }

    public function test_draft_form_used_by_assignment_cannot_be_deleted(): void
    {
        $world = $this->world();
        $draft = FormTemplate::factory()->create(['campaign_id' => $world['campaign']->id, 'status' => FormStatus::Draft]);
        $world['assignment']->update(['form_template_id' => $draft->id]);

        $this->actingAs($world['eo'])->delete('/forms/'.$draft->id)->assertSessionHasErrors('form');

        $this->assertModelExists($draft);
        $this->assertSame(1, Assignment::query()->count());
    }

    public function test_non_superadmin_cannot_delete_form(): void
    {
        $world = $this->world();
        $draft = FormTemplate::factory()->create(['campaign_id' => $world['campaign']->id, 'status' => FormStatus::Draft]);

        $this->actingAs($world['userA'])->delete('/forms/'.$draft->id)->assertForbidden();
        $this->actingAs($world['spg'])->delete('/forms/'.$draft->id)->assertForbidden();
        $this->assertModelExists($draft);
    }
}
