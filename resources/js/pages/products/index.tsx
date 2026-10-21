import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

interface ProductRow {
    id: number;
    name: string;
    sku: string | null;
    client: { name: string };
}

export default function ProductsIndex({ products }: { products: { data: ProductRow[]; links: { url: string | null; label: string; active: boolean }[] } }) {
    const canEdit = usePage<SharedData>().props.auth.user?.role === 'superadmin';

    return (
        <AppLayout breadcrumbs={[{ title: 'Produk', href: '/products' }]}>
            <Head title="Produk" />
            <PageHeader
                title="Produk"
                description="Produk milik masing-masing client."
                action={canEdit ? { href: route('products.create'), label: 'Tambah produk' } : undefined}
            />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Nama</th>
                            <th className="px-3 py-2 text-left">SKU</th>
                            <th className="px-3 py-2 text-left">Client</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        {products.data.map((product) => (
                            <tr key={product.id} className="border-t">
                                <td className="px-3 py-2">{product.name}</td>
                                <td className="px-3 py-2">{product.sku}</td>
                                <td className="px-3 py-2">{product.client.name}</td>
                                <td className="px-3 py-2 text-right">
                                    {canEdit && (
                                        <Button asChild size="sm" variant="outline">
                                            <Link href={route('products.edit', product.id)}>Ubah</Link>
                                        </Button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination links={products.links} />
        </AppLayout>
    );
}
