<?php

namespace App\Http\Controllers;

use App\Domain\Audit\Services\AuditLogger;
use App\Enums\UserRole;
use App\Http\Requests\Users\StoreUserRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('users/index', [
            'users' => User::query()
                ->with('client')
                ->where('role', '!=', UserRole::Superadmin)
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/create', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create([
            ...$request->safe()->only(['name', 'username', 'password', 'role']),
            'client_id' => $request->input('role') === UserRole::Client->value ? $request->integer('client_id') : null,
        ]);

        return redirect()->route('users.index')->with('success', 'Akun berhasil dibuat.');
    }

    /**
     * Client & SPG tidak punya lupa password mandiri; EO yang mengatur password baru.
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

        $audit->record($request->user(), 'user.password_reset', $user);

        return back()->with('success', 'Password '.$user->name.' berhasil direset.');
    }
}
