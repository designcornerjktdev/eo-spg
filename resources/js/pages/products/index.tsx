import FilterBar from '@/components/filter-bar';
import IconAction from '@/components/icon-action';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import AppLayout from '@/layouts/app-layout';
import { SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Pencil } from 'lucide-react';

interface ProductRow {
    id: number;
    name: string;
    sku: string | null;
    campaigns_count: number;
    client: { name: string };
}

export default function ProductsIndex({
    products,
    filters,
    clients,
}: {
    products: { data: ProductRow[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { search?: string; client_id?: number | null };
    clients: { id: number; name: string }[];
}) {
    const canEdit = usePage<SharedData>().props.auth.user?.role === 'superadmin';

    return (
        <AppLayout breadcrumbs={[{ title: 'Produk', href: '/products' }]}>
            <Head title="Produk" />
            <PageHeader
                title="Produk"
                description="Produk milik masing-masing client."
                action={canEdit ? { href: route('products.create'), label: 'Tambah produk' } : undefined}
            />
            <FilterBar
                url={route('products.index')}
                filters={filters}
                searchPlaceholder="Cari nama atau SKU"
                selects={canEdit ? [{ name: 'client_id', label: 'Semua client', options: clients.map((c) => ({ value: c.id, label: c.name })) }] : []}
            />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Nama</th>
                            <th className="px-3 py-2 text-left">SKU</th>
                            <th className="px-3 py-2 text-left">Client</th>
                            <th className="px-3 py-2 text-left">Dipakai di campaign</th>
                            {canEdit && <th className="px-3 py-2 text-left">Aksi</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {products.data.map((product) => (
                            <tr key={product.id} className="border-t">
                                <td className="px-3 py-2">{product.name}</td>
                                <td className="px-3 py-2 font-mono">{product.sku ?? '-'}</td>
                                <td className="px-3 py-2">{product.client.name}</td>
                                <td className="px-3 py-2">{product.campaigns_count}</td>
                                {canEdit && (
                                    <td className="px-3 py-1">
                                        <IconAction icon={Pencil} label="Edit produk" href={route('products.edit', product.id)} />
                                    </td>
                                )}
                            </tr>
                        ))}
                        {products.data.length === 0 && (
                            <tr>
                                <td colSpan={5} className="text-muted-foreground px-3 py-8 text-center">
                                    Tidak ada produk yang cocok.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={products.links} />
        </AppLayout>
    );
}
