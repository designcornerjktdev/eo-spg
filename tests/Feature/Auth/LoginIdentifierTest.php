<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
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

    public function test_client_and_spg_log_in_with_username_only(): void
    {
        $spg = User::factory()->spg()->create(['username' => 'spg1', 'email' => 'spg1@eo.test']);

        $this->post('/login', ['login' => 'spg1@eo.test', 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->post('/login', ['login' => 'SPG1', 'password' => 'password'])->assertRedirect('/tasks');
        $this->assertAuthenticatedAs($spg);
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        User::factory()->spg()->create(['username' => 'inactive', 'is_active' => false]);

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

    public function test_superadmin_can_reset_password_of_spg(): void
    {
        $eo = User::factory()->superadmin()->create();
        $spg = User::factory()->spg()->create();

        $this->actingAs($eo)->put('/users/'.$spg->id.'/password', [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPassword123', $spg->refresh()->password));
        $this->assertTrue(AuditLog::query()->where('action', 'user.password_reset')->exists());
    }

    public function test_only_superadmin_can_reset_others_and_never_another_superadmin(): void
    {
        $eo = User::factory()->superadmin()->create();
        $otherEo = User::factory()->superadmin()->create();
        $spg = User::factory()->spg()->create();
        $payload = ['password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];

        $this->actingAs($spg)->put('/users/'.$spg->id.'/password', $payload)->assertForbidden();
        $this->actingAs($eo)->put('/users/'.$otherEo->id.'/password', $payload)->assertForbidden();
    }

    public function test_superadmin_creates_account_with_username(): void
    {
        $eo = User::factory()->superadmin()->create();

        $this->actingAs($eo)->post('/users', [
            'name' => 'New SPG',
            'username' => 'spg.new',
            'password' => 'password',
            'role' => 'spg',
        ])->assertSessionHasNoErrors();

        $this->post('/logout');
        $this->post('/login', ['login' => 'spg.new', 'password' => 'password'])->assertRedirect('/tasks');
    }
}
