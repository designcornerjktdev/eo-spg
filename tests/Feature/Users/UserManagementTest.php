<?php

namespace Tests\Feature\Users;

use App\Models\AuditLog;
use App\Models\FormSubmission;
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
        User::factory()->spg()->create(['name' => 'Siti', 'username' => 'siti']);
        User::factory()->spg()->create(['name' => 'Budi', 'username' => 'budi', 'is_active' => false]);

        $this->actingAs($eo)->get('/users?search=sit')
            ->assertInertia(fn ($page) => $page->component('users/index')->has('users.data', 1)->where('users.data.0.username', 'siti'));
        $this->actingAs($eo)->get('/users?status=inactive')
            ->assertInertia(fn ($page) => $page->has('users.data', 1)->where('users.data.0.username', 'budi'));
    }

    public function test_superadmin_can_update_user_and_deactivate_logs_them_out(): void
    {
        $eo = User::factory()->superadmin()->create();
        $spg = User::factory()->spg()->create(['username' => 'oldname']);

        $this->actingAs($eo)->get('/users/'.$spg->id.'/edit')->assertOk();
        $this->actingAs($eo)->put('/users/'.$spg->id, [
            'name' => 'New Name',
            'username' => 'newname',
            'is_active' => false,
        ])->assertSessionHasNoErrors()->assertRedirect('/users');

        $spg->refresh();
        $this->assertSame('newname', $spg->username);
        $this->assertFalse($spg->is_active);
        $this->assertTrue(AuditLog::query()->where('action', 'user.updated')->exists());

        $this->post('/logout');
        $this->post('/login', ['login' => 'newname', 'password' => 'password'])->assertSessionHasErrors('login');
    }

    public function test_username_must_stay_unique_and_role_cannot_change(): void
    {
        $eo = User::factory()->superadmin()->create();
        User::factory()->spg()->create(['username' => 'taken']);
        $spg = User::factory()->spg()->create();

        $this->actingAs($eo)->put('/users/'.$spg->id, [
            'name' => 'X',
            'username' => 'taken',
            'is_active' => true,
            'role' => 'client',
        ])->assertSessionHasErrors('username');

        $this->assertSame('spg', $spg->refresh()->role->value);
    }

    public function test_user_without_history_can_be_deleted(): void
    {
        $eo = User::factory()->superadmin()->create();
        $spg = User::factory()->spg()->create();

        $this->actingAs($eo)->delete('/users/'.$spg->id)->assertRedirect('/users');

        $this->assertModelMissing($spg);
        $this->assertTrue(AuditLog::query()->where('action', 'user.deleted')->exists());
    }

    public function test_user_with_submissions_cannot_be_deleted(): void
    {
        $world = $this->world();
        $this->actingAs($world['spg'])->post('/tasks/'.$world['assignment']->id.'/submissions', [
            'answers' => $this->validAnswers(),
            'consent_accepted' => true,
        ]);

        $this->actingAs($world['eo'])->delete('/users/'.$world['spg']->id)->assertSessionHasErrors('user');

        $this->assertModelExists($world['spg']);
        $this->assertSame(1, FormSubmission::query()->count());
    }

    public function test_superadmin_accounts_and_non_admins_are_protected(): void
    {
        $eo = User::factory()->superadmin()->create();
        $otherEo = User::factory()->superadmin()->create();
        $spg = User::factory()->spg()->create();
        $client = User::factory()->clientUser()->create();

        $this->actingAs($eo)->get('/users/'.$otherEo->id.'/edit')->assertForbidden();
        $this->actingAs($eo)->put('/users/'.$otherEo->id, ['name' => 'X', 'username' => 'x', 'is_active' => true])->assertForbidden();
        $this->actingAs($eo)->delete('/users/'.$otherEo->id)->assertForbidden();

        $this->actingAs($client)->get('/users')->assertForbidden();
        $this->actingAs($spg)->delete('/users/'.$client->id)->assertForbidden();
        $this->assertModelExists($client);
    }
}
