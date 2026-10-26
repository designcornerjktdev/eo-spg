<?php

namespace Tests\Feature\Users;

use App\Enums\CampaignStatus;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_admin_signs_in_with_username(): void
    {
        $admin = User::factory()->adminUser()->create(['username' => 'ops']);

        $this->post('/login', ['login' => 'ops', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_signs_in_with_email_and_resets_own_password(): void
    {
        Notification::fake();
        $admin = User::factory()->adminUser()->create(['email' => 'ops@eo.test']);
        $client = User::factory()->clientUser()->create(['email' => 'client@eo.test']);

        $this->post('/login', ['login' => 'OPS@eo.test', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->post('/logout');

        // A client with an email still cannot sign in with it or reset by themselves.
        $this->post('/login', ['login' => 'client@eo.test', 'password' => 'password'])->assertSessionHasErrors();

        $this->post('/forgot-password', ['email' => 'ops@eo.test']);
        $this->post('/forgot-password', ['email' => 'client@eo.test']);
        Notification::assertSentTo($admin, ResetPassword::class);
        Notification::assertNotSentTo($client, ResetPassword::class);

        Notification::assertSentTo($admin, ResetPassword::class, function (ResetPassword $notification) use ($admin) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $admin->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertSessionHasNoErrors();

            return true;
        });
        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
    }

    public function test_admin_accounts_require_an_email_and_clients_have_none(): void
    {
        $world = $this->world();
        $base = ['name' => 'Ops', 'username' => 'ops', 'password' => 'password-123'];

        $this->actingAs($world['eo'])->post('/users', [...$base, 'role' => 'admin'])->assertSessionHasErrors('email');
        $this->actingAs($world['eo'])->post('/users', [...$base, 'role' => 'client', 'email' => 'c@eo.test'])->assertSessionHasErrors('email');
        $this->actingAs($world['eo'])->post('/users', [...$base, 'role' => 'admin', 'email' => $world['eo']->email])->assertSessionHasErrors('email');

        $this->actingAs($world['eo'])->post('/users', [...$base, 'role' => 'admin', 'email' => 'ops@eo.test'])->assertSessionHasNoErrors();
        $this->assertSame('ops@eo.test', User::query()->where('username', 'ops')->value('email'));
    }

    public function test_admin_runs_campaigns_forms_and_entries_of_every_campaign(): void
    {
        $world = $this->world();
        $admin = User::factory()->adminUser()->create();
        $this->submitPublicEntry($world['otherLocation'])->assertSessionHasNoErrors();
        $entry = FormSubmission::query()->sole();

        $this->actingAs($admin);
        $this->get('/campaigns')->assertOk()->assertInertia(fn ($page) => $page->has('campaigns.data', 2));
        $this->get('/campaigns/create')->assertOk();
        $this->get('/campaigns/'.$world['otherCampaign']->id.'/edit')->assertOk();
        $this->post('/campaigns/'.$world['campaign']->id.'/close')->assertSessionHasNoErrors();
        $this->assertSame(CampaignStatus::Closed, $world['campaign']->fresh()->status);

        $this->get('/forms')->assertOk();
        $this->get('/forms/create')->assertOk();
        $this->get('/forms/'.$world['form']->id.'/edit')->assertOk();

        $this->get('/submissions')->assertOk()->assertInertia(fn ($page) => $page->has('submissions.data', 1));
        $this->get('/submissions/'.$entry->id)->assertOk();
        $this->get('/submissions/export')->assertOk();
        $this->post('/submissions/'.$entry->id.'/void', ['void_reason' => 'Duplicate entry from testing'])->assertSessionHasNoErrors();
        $this->assertTrue($entry->fresh()->isVoid());
    }

    public function test_admin_cannot_manage_accounts_audit_or_delete_campaigns(): void
    {
        $world = $this->world();
        $admin = User::factory()->adminUser()->create();
        $world['campaign']->update(['status' => CampaignStatus::Draft]);

        $this->actingAs($admin);
        $this->get('/users')->assertForbidden();
        $this->get('/users/create')->assertForbidden();
        $this->get('/audit')->assertForbidden();
        $this->delete('/campaigns/'.$world['campaign']->id)->assertForbidden();
        $this->assertNotNull($world['campaign']->fresh());
    }

    public function test_superadmin_creates_admin_without_campaign_grants(): void
    {
        $world = $this->world();

        $this->actingAs($world['eo'])->post('/users', [
            'name' => 'Ops Team',
            'username' => 'ops.team',
            'email' => 'ops.team@eo.test',
            'password' => 'password-123',
            'role' => 'admin',
            'campaign_ids' => [$world['campaign']->id],
        ])->assertSessionHasNoErrors();

        $admin = User::query()->where('username', 'ops.team')->sole();
        $this->assertTrue($admin->isAdmin());
        $this->assertSame(0, $admin->campaigns()->count());
        $this->assertTrue($admin->canViewCampaign($world['otherCampaign']->id));

        $this->actingAs($world['eo'])->post('/users', [
            'name' => 'Sneaky',
            'username' => 'sneaky',
            'password' => 'password-123',
            'role' => 'superadmin',
        ])->assertSessionHasErrors('role');
    }
}
