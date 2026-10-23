<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginIdentifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_logs_in_with_email_only(): void
    {
        $eo = User::factory()->superadmin()->create(['email' => 'eo@eo.test', 'username' => 'eo']);

        $this->post('/login', ['login' => 'eo', 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->post('/login', ['login' => 'EO@eo.test', 'password' => 'password']);
        $this->assertAuthenticatedAs($eo);
    }

    public function test_clients_log_in_with_username_only(): void
    {
        $client = User::factory()->clientUser()->create(['username' => 'mayora', 'email' => 'mayora@eo.test']);

        $this->post('/login', ['login' => 'mayora@eo.test', 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->post('/login', ['login' => 'MAYORA', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($client);
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        User::factory()->clientUser()->create(['username' => 'inactive', 'is_active' => false]);

        $this->post('/login', ['login' => 'inactive', 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_reset_link_is_only_sent_to_superadmin(): void
    {
        Notification::fake();

        $eo = User::factory()->superadmin()->create();
        $client = User::factory()->clientUser()->create(['email' => 'client@eo.test']);

        $this->post('/forgot-password', ['email' => $client->email])->assertSessionHas('status');
        $this->post('/forgot-password', ['email' => $eo->email])->assertSessionHas('status');

        Notification::assertNotSentTo($client, ResetPassword::class);
        Notification::assertSentTo($eo, ResetPassword::class);
    }

    public function test_superadmin_can_reset_password_of_client(): void
    {
        $eo = User::factory()->superadmin()->create();
        $client = User::factory()->clientUser()->create();

        $this->actingAs($eo)->put('/users/'.$client->id.'/password', [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPassword123', $client->refresh()->password));
        $this->assertTrue(AuditLog::query()->where('action', 'user.password_reset')->exists());
    }

    public function test_only_superadmin_can_reset_others_and_never_another_superadmin(): void
    {
        $eo = User::factory()->superadmin()->create();
        $otherEo = User::factory()->superadmin()->create();
        $client = User::factory()->clientUser()->create();
        $payload = ['password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];

        $this->actingAs($client)->put('/users/'.$client->id.'/password', $payload)->assertForbidden();
        $this->actingAs($eo)->put('/users/'.$otherEo->id.'/password', $payload)->assertForbidden();
    }

    public function test_superadmin_creates_account_with_username(): void
    {
        $eo = User::factory()->superadmin()->create();

        $campaign = Campaign::factory()->create();

        $this->actingAs($eo)->post('/users', [
            'name' => 'New Client',
            'username' => 'client.new',
            'password' => 'password',
            'role' => 'client',
            'campaign_ids' => [$campaign->id],
        ])->assertSessionHasNoErrors();

        $this->post('/logout');
        $this->post('/login', ['login' => 'client.new', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertTrue(User::query()->where('username', 'client.new')->sole()->canViewCampaign($campaign->id));
    }
}
