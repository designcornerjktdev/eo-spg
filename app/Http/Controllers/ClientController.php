<?php

namespace App\Http\Controllers;

use App\Domain\Audit\Services\AuditLogger;
use App\Enums\UserRole;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $search = $request->string('search')->trim()->value();

        $clients = Client::query()
            ->when($request->user()->isClient(), fn ($query) => $query->whereKey($request->user()->client_id))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('code', 'like', '%'.$search.'%')))
            ->withCount(['products', 'campaigns', 'users'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('clients/create');
    }

    public function store(StoreClientRequest $request, AuditLogger $audit): RedirectResponse
    {
        $client = DB::transaction(function () use ($request, $audit) {
            $client = Client::query()->create($request->only(['name', 'code', 'notes']));

            if ($request->filled('account_username')) {
                User::query()->create([
                    'name' => $request->string('account_name'),
                    'username' => $request->string('account_username'),
                    'password' => $request->string('account_password'),
                    'role' => UserRole::Client,
                    'client_id' => $client->id,
                ]);
            }

            $audit->record($request->user(), 'client.created', $client, null, $client->toArray());

            return $client;
        });

        return redirect()->route('clients.show', $client)->with('success', 'Client berhasil dibuat.');
    }

    public function show(Client $client): Response
    {
        $this->authorize('view', $client);

        $client->load(['users', 'products', 'campaigns']);

        return Inertia::render('clients/show', [
            'client' => $client,
        ]);
    }

    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('clients/edit', [
            'client' => $client,
            'deleteBlocker' => $this->deleteBlocker($client),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client, AuditLogger $audit): RedirectResponse
    {
        $old = $client->only(['name', 'code', 'notes']);
        $client->update($request->validated());
        $audit->record($request->user(), 'client.updated', $client, $old, $client->only(['name', 'code', 'notes']));

        return redirect()->route('clients.show', $client)->with('success', 'Client diperbarui.');
    }

    public function destroy(Request $request, Client $client, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $client);

        if ($blocker = $this->deleteBlocker($client)) {
            throw ValidationException::withMessages(['client' => $blocker]);
        }

        DB::transaction(function () use ($request, $client, $audit) {
            // Akun login client ikut dinonaktifkan dan dikeluarkan agar tidak tersisa akses ke client yang dihapus.
            $userIds = $client->users()->pluck('id');
            $client->users()->update(['is_active' => false]);
            DB::table(config('session.table', 'sessions'))->whereIn('user_id', $userIds)->delete();

            $audit->record($request->user(), 'client.deleted', $client, $client->toArray());
            $client->delete();
        });

        return redirect()->route('clients.index')->with('success', 'Client '.$client->name.' dihapus.');
    }

    /**
     * Client yang sudah punya campaign atau produk tidak boleh dihapus agar laporan dan riwayatnya tetap utuh.
     */
    private function deleteBlocker(Client $client): ?string
    {
        if ($client->campaigns()->exists()) {
            return 'Client ini sudah punya campaign, jadi tidak bisa dihapus.';
        }

        if ($client->products()->exists()) {
            return 'Client ini masih punya produk. Hapus produknya dulu.';
        }

        return null;
    }
}
