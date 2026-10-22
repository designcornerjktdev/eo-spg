import IconAction from '@/components/icon-action';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Eye, Pencil } from 'lucide-react';
import { useState } from 'react';

interface ClientRow {
    id: number;
    name: string;
    code: string;
    products_count: number;
    campaigns_count: number;
    users_count: number;
}

export default function ClientsIndex({
    clients,
    filters,
}: {
    clients: { data: ClientRow[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { search?: string };
}) {
    const isEo = usePage<SharedData>().props.auth.user?.role === 'superadmin';
    const [search, setSearch] = useState(filters.search ?? '');

    return (
        <AppLayout breadcrumbs={[{ title: 'Client', href: '/clients' }]}>
            <Head title="Client" />
            <PageHeader
                title="Client"
                description="Perusahaan pemilik produk dan campaign."
                action={isEo ? { href: route('clients.create'), label: 'Tambah client' } : undefined}
            />

            {isEo && (
                <form
                    className="flex gap-2"
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(route('clients.index'), { search }, { preserveState: true, replace: true });
                    }}
                >
                    <Input className="w-full sm:w-64" placeholder="Cari nama atau kode" value={search} onChange={(e) => setSearch(e.target.value)} />
                    <Button type="submit" variant="outline">
                        Cari
                    </Button>
                </form>
            )}

            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Nama</th>
                            <th className="px-3 py-2 text-left">Kode</th>
                            <th className="px-3 py-2 text-left">Produk</th>
                            <th className="px-3 py-2 text-left">Campaign</th>
                            <th className="px-3 py-2 text-left">Akun</th>
                            <th className="px-3 py-2 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {clients.data.map((client) => (
                            <tr key={client.id} className="border-t">
                                <td className="px-3 py-2">{client.name}</td>
                                <td className="px-3 py-2 font-mono">{client.code}</td>
                                <td className="px-3 py-2">{client.products_count}</td>
                                <td className="px-3 py-2">{client.campaigns_count}</td>
                                <td className="px-3 py-2">{client.users_count}</td>
                                <td className="px-3 py-1">
                                    <div className="flex gap-1">
                                        <IconAction icon={Eye} label="Lihat detail" href={route('clients.show', client.id)} />
                                        {isEo && <IconAction icon={Pencil} label="Edit client" href={route('clients.edit', client.id)} />}
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {clients.data.length === 0 && (
                            <tr>
                                <td colSpan={6} className="text-muted-foreground px-3 py-8 text-center">
                                    Tidak ada client yang cocok.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={clients.links} />
        </AppLayout>
    );
}
