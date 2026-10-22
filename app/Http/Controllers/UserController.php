<?php

namespace App\Http\Controllers;

use App\Domain\Audit\Services\AuditLogger;
use App\Enums\UserRole;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Client;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Client & SPG account management by the superadmin. EO accounts are not managed here.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'role', 'status']);

        return Inertia::render('users/index', [
            'users' => User::query()
                ->with('client')
                ->where('role', '!=', UserRole::Superadmin)
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('username', 'like', '%'.$search.'%')))
                ->when(in_array($filters['role'] ?? null, [UserRole::Client->value, UserRole::Spg->value], true), fn ($query) => $query->where('role', $filters['role']))
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
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = User::query()->create([
            ...$request->safe()->only(['name', 'username', 'password', 'role']),
            'client_id' => $request->input('role') === UserRole::Client->value ? $request->integer('client_id') : null,
        ]);

        $audit->record($request->user(), 'user.created', $user, null, $user->only(['name', 'username', 'role', 'client_id']));

        return redirect()->route('users.index')->with('success', 'Account created.');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);
        abort_if($user->isSuperadmin(), 403);

        return Inertia::render('users/edit', [
            'user' => $user->only(['id', 'name', 'username', 'role', 'client_id', 'is_active']),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'deletable' => $this->isDeletable($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $old = $user->only(['name', 'username', 'client_id', 'is_active']);

        $user->update([
            ...$request->safe()->only(['name', 'username', 'is_active']),
            'client_id' => $user->role === UserRole::Client ? $request->integer('client_id') : null,
        ]);

        // Deactivated accounts are signed out of every session immediately.
        if ($old['is_active'] && ! $user->is_active) {
            $this->logoutEverywhere($user);
        }

        $audit->record($request->user(), 'user.updated', $user, $old, $user->only(array_keys($old)));

        return redirect()->route('users.index')->with('success', 'Account '.$user->name.' updated.');
    }

    /**
     * Hard delete only for accounts without history; the assignment/submission -> users FKs cascade,
     * so deleting an account that has worked would delete its data too.
     */
    public function destroy(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $user);
        abort_if($user->isSuperadmin(), 403);

        if (! $this->isDeletable($user)) {
            throw ValidationException::withMessages([
                'user' => 'This account already has assignments or entries, so it cannot be deleted. Deactivate it instead.',
            ]);
        }

        DB::transaction(function () use ($request, $user, $audit) {
            $audit->record($request->user(), 'user.deleted', $user, $user->only(['name', 'username', 'role', 'client_id']));
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

    private function isDeletable(User $user): bool
    {
        return ! $user->assignments()->exists()
            && ! FormSubmission::query()->where('submitted_by', $user->id)->exists();
    }

    private function logoutEverywhere(User $user): void
    {
        DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
    }
}
