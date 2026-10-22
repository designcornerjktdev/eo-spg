<?php

namespace App\Http\Controllers;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Requests\Products\ProductRequest;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'client_id' => $request->integer('client_id') ?: null,
        ];

        $products = Product::query()
            ->visibleTo($request->user())
            ->with('client')
            ->withCount('campaigns')
            ->when($filters['search'] !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$filters['search'].'%')
                ->orWhere('sku', 'like', '%'.$filters['search'].'%')))
            ->when($filters['client_id'] && $request->user()->isSuperadmin(), fn ($query) => $query->where('client_id', $filters['client_id']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('products/index', [
            'products' => $products,
            'filters' => $filters,
            'clients' => $request->user()->isSuperadmin() ? Client::query()->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('products/create', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(ProductRequest $request, AuditLogger $audit): RedirectResponse
    {
        $product = Product::query()->create($request->validated());
        $audit->record($request->user(), 'product.created', $product, null, $product->only(['name', 'sku', 'client_id']));

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambah.');
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        return Inertia::render('products/edit', [
            'product' => $product,
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'deleteBlocker' => $this->deleteBlocker($product),
        ]);
    }

    public function update(ProductRequest $request, Product $product, AuditLogger $audit): RedirectResponse
    {
        $old = $product->only(['name', 'sku', 'client_id']);
        $product->update($request->validated());
        $audit->record($request->user(), 'product.updated', $product, $old, $product->only(array_keys($old)));

        return redirect()->route('products.index')->with('success', 'Produk diperbarui.');
    }

    public function destroy(Request $request, Product $product, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($blocker = $this->deleteBlocker($product)) {
            throw ValidationException::withMessages(['product' => $blocker]);
        }

        $audit->record($request->user(), 'product.deleted', $product, $product->only(['name', 'sku', 'client_id']));
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk '.$product->name.' dihapus.');
    }

    /**
     * Produk yang sudah dipakai campaign tetap disimpan agar laporan campaign tidak kehilangan referensi produknya.
     */
    private function deleteBlocker(Product $product): ?string
    {
        return $product->campaigns()->exists()
            ? 'Produk ini sudah dipakai di campaign, jadi tidak bisa dihapus.'
            : null;
    }
}
