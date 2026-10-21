import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

interface ClientRow {
    id: number;
    name: string;
    code: string;
    products_count: number;
    campaigns_count: number;
    users_count: number;
}

export default function ClientsIndex({ clients }: { clients: { data: ClientRow[]; links: { url: string | null; label: string; active: boolean }[] } }) {
    const role = usePage<SharedData>().props.auth.user?.role;

    return (
        <AppLayout breadcrumbs={[{ title: 'Client', href: '/clients' }]}>
            <Head title="Client" />
            <PageHeader
                title="Client"
                description="Perusahaan pemilik produk dan campaign."
                action={role === 'superadmin' ? { href: route('clients.create'), label: 'Tambah client' } : undefined}
            />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Nama</th>
                            <th className="px-3 py-2 text-left">Kode</th>
                            <th className="px-3 py-2 text-left">Produk</th>
                            <th className="px-3 py-2 text-left">Campaign</th>
                            <th className="px-3 py-2 text-left">Akun</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        {clients.data.map((client) => (
                            <tr key={client.id} className="border-t">
                                <td className="px-3 py-2">{client.name}</td>
                                <td className="px-3 py-2">{client.code}</td>
                                <td className="px-3 py-2">{client.products_count}</td>
                                <td className="px-3 py-2">{client.campaigns_count}</td>
                                <td className="px-3 py-2">{client.users_count}</td>
                                <td className="px-3 py-2 text-right">
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={route('clients.show', client.id)}>Lihat</Link>
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination links={clients.links} />
        </AppLayout>
    );
}
