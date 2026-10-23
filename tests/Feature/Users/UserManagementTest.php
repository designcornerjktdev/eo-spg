<?php

namespace Tests\Feature\Users;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCampaignWorld;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use BuildsCampaignWorld, RefreshDatabase;

    public function test_superadmin_can_search_and_filter_users(): void
    {
        $eo = User::factory()->superadmin()->create();
        User::factory()->clientUser()->create(['name' => 'Siti', 'username' => 'siti']);
        User::factory()->clientUser()->create(['name' => 'Budi', 'username' => 'budi', 'is_active' => false]);

        $this->actingAs($eo)->get('/users?search=sit')
            ->assertInertia(fn ($page) => $page->component('users/index')->has('users.data', 1)->where('users.data.0.username', 'siti'));
        $this->actingAs($eo)->get('/users?status=inactive')
            ->assertInertia(fn ($page) => $page->has('users.data', 1)->where('users.data.0.username', 'budi'));
    }

    public function test_superadmin_can_update_user_and_deactivate_logs_them_out(): void
    {
        $eo = User::factory()->superadmin()->create();
        $account = User::factory()->clientUser()->create(['username' => 'oldname']);

        $this->actingAs($eo)->get('/users/'.$account->id.'/edit')->assertOk();
        $this->actingAs($eo)->put('/users/'.$account->id, [
            'name' => 'New Name',
            'username' => 'newname',
            'is_active' => false,
            'client_id' => $account->client_id,
        ])->assertSessionHasNoErrors()->assertRedirect('/users');

        $account->refresh();
        $this->assertSame('newname', $account->username);
        $this->assertFalse($account->is_active);
        $this->assertTrue(AuditLog::query()->where('action', 'user.updated')->exists());

        $this->post('/logout');
        $this->post('/login', ['login' => 'newname', 'password' => 'password'])->assertSessionHasErrors('login');
    }

    public function test_username_must_stay_unique_and_role_cannot_change(): void
    {
        $eo = User::factory()->superadmin()->create();
        User::factory()->clientUser()->create(['username' => 'taken']);
        $account = User::factory()->clientUser()->create();

        $this->actingAs($eo)->put('/users/'.$account->id, [
            'name' => 'X',
            'username' => 'taken',
            'is_active' => true,
            'role' => 'superadmin',
        ])->assertSessionHasErrors('username');

        $this->assertSame('client', $account->refresh()->role->value);
    }

    public function test_client_account_can_be_deleted(): void
    {
        $eo = User::factory()->superadmin()->create();
        $account = User::factory()->clientUser()->create();

        $this->actingAs($eo)->delete('/users/'.$account->id)->assertRedirect('/users');

        $this->assertModelMissing($account);
        $this->assertTrue(AuditLog::query()->where('action', 'user.deleted')->exists());
    }

    public function test_superadmin_cannot_delete_or_deactivate_own_account(): void
    {
        $eo = User::factory()->superadmin()->create();

        $this->actingAs($eo)->delete('/users/'.$eo->id)->assertForbidden();
        $this->actingAs($eo)->put('/users/'.$eo->id, ['name' => 'X', 'username' => 'x', 'is_active' => false])->assertForbidden();

        $this->assertModelExists($eo);
        $this->assertTrue($eo->refresh()->is_active);
    }

    public function test_superadmin_accounts_and_non_admins_are_protected(): void
    {
        $eo = User::factory()->superadmin()->create();
        $otherEo = User::factory()->superadmin()->create();
        $account = User::factory()->clientUser()->create();
        $client = User::factory()->clientUser()->create();

        $this->actingAs($eo)->get('/users/'.$otherEo->id.'/edit')->assertForbidden();
        $this->actingAs($eo)->put('/users/'.$otherEo->id, ['name' => 'X', 'username' => 'x', 'is_active' => true])->assertForbidden();
        $this->actingAs($eo)->delete('/users/'.$otherEo->id)->assertForbidden();

        $this->actingAs($client)->get('/users')->assertForbidden();
        $this->actingAs($client)->delete('/users/'.$account->id)->assertForbidden();
        $this->assertModelExists($client);
    }
}
