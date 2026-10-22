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
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::query()
            ->when($request->user()->isClient(), fn ($query) => $query->whereKey($request->user()->client_id))
            ->withCount(['products', 'campaigns', 'users'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('clients/index', [
            'clients' => $clients,
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
        $audit->record($request->user(), 'client.deleted', $client, $client->toArray());
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client dihapus.');
    }
}
