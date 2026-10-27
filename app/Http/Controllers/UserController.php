<?php

namespace App\Http\Controllers;

use App\Domain\Audit\Services\AuditLogger;
use App\Enums\UserRole;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Client and admin account management by the superadmin. Admins only see client accounts and change their campaign access.
 * The superadmin account itself is not managed here.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'role', 'status']);

        return Inertia::render('users/index', [
            'users' => User::query()
                ->with('campaigns:id,name')
                ->where('role', '!=', UserRole::Superadmin)
                ->when(! $request->user()->isSuperadmin(), fn ($query) => $query->where('role', UserRole::Client))
                ->when(in_array($filters['role'] ?? null, [UserRole::Client->value, UserRole::Admin->value], true), fn ($query) => $query->where('role', $filters['role']))
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('username', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')))
                ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
                ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/create', [
            'campaigns' => $this->campaignOptions(),
        ]);
    }

    public function store(StoreUserRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::query()->create($request->safe()->only(['name', 'username', 'email', 'password', 'role']));
            // Admins see every campaign, so campaign access only applies to clients.
            $user->campaigns()->sync($user->isClient() ? $request->input('campaign_ids', []) : []);

            return $user;
        });

        $audit->record($request->user(), 'user.created', $user, null, [
            ...$user->only(['name', 'username', 'role']),
            'campaign_ids' => $user->campaigns()->pluck('campaigns.id')->all(),
        ]);

        return redirect()->route('users.index')->with('success', 'Account created.');
    }

    public function edit(Request $request, User $user): Response
    {
        abort_if($user->isSuperadmin(), 403);
        abort_unless($request->user()->can('update', $user) || $request->user()->can('manageCampaignAccess', $user), 403);

        return Inertia::render('users/edit', [
            'user' => [
                ...$user->only(['id', 'name', 'username', 'email', 'role', 'is_active']),
                'campaign_ids' => $user->campaigns()->pluck('campaigns.id'),
            ],
            'campaigns' => $this->campaignOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $old = [
            ...$user->only(['name', 'username', 'email', 'is_active']),
            'campaign_ids' => $user->campaigns()->pluck('campaigns.id')->all(),
        ];

        DB::transaction(function () use ($request, $user) {
            if ($request->user()->isSuperadmin()) {
                $user->update($request->safe()->only(['name', 'username', 'email', 'is_active']));
            }
            $user->campaigns()->sync($user->isClient() ? $request->input('campaign_ids', []) : []);
        });

        // Deactivated accounts are signed out of every session immediately.
        if ($old['is_active'] && ! $user->is_active) {
            $this->logoutEverywhere($user);
        }

        $audit->record($request->user(), 'user.updated', $user, $old, [
            ...$user->only(['name', 'username', 'is_active']),
            'campaign_ids' => $user->campaigns()->pluck('campaigns.id')->all(),
        ]);

        return redirect()->route('users.index')->with('success', 'Account '.$user->name.' updated.');
    }

    /**
     * Client accounts own no entries (entries are anonymous), so they can be deleted outright.
     */
    public function destroy(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $user);
        abort_if($user->isSuperadmin(), 403);

        DB::transaction(function () use ($request, $user, $audit) {
            $audit->record($request->user(), 'user.deleted', $user, $user->only(['name', 'username', 'role']));
            $this->logoutEverywhere($user);
            $user->delete();
        });

        return redirect()->route('users.index')->with('success', 'Account '.$user->name.' deleted.');
    }

    /**
     * Clients & SPGs have no self-service password reset; the EO admin sets a new password.
     */
    public function resetPassword(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $user);
        abort_if($user->isSuperadmin(), 403);

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($request->string('password')->value()),
            'remember_token' => Str::random(60),
        ])->save();

        $this->logoutEverywhere($user);
        $audit->record($request->user(), 'user.password_reset', $user);

        return back()->with('success', 'Password for '.$user->name.' has been reset.');
    }

    private function logoutEverywhere(User $user): void
    {
        DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
    }

    /**
     * @return Collection<int, array{id: int, name: string, status: string}>
     */
    private function campaignOptions()
    {
        return Campaign::query()->latest()->get(['id', 'name', 'status'])
            ->map(fn (Campaign $campaign) => ['id' => $campaign->id, 'name' => $campaign->name, 'status' => $campaign->status->value]);
    }
}
