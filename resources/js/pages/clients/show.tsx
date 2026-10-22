import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

interface ClientShow {
    id: number;
    name: string;
    code: string;
    notes: string | null;
    users: { id: number; name: string; username: string | null; email: string | null }[];
    products: { id: number; name: string }[];
    campaigns: { id: number; name: string; status: string }[];
}

export default function ClientShow({ client }: { client: ClientShow }) {
    const canEdit = usePage<SharedData>().props.auth.user?.role === 'superadmin';

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Client', href: '/clients' },
                { title: client.name, href: route('clients.show', client.id) },
            ]}
        >
            <Head title={client.name} />
            <div className="flex items-start justify-between">
                <PageHeader title={client.name} description={`Kode ${client.code}`} />
                {canEdit && (
                    <Button asChild variant="outline">
                        <Link href={route('clients.edit', client.id)}>Ubah</Link>
                    </Button>
                )}
            </div>
            {client.notes && <p className="text-muted-foreground text-sm">{client.notes}</p>}
            <div className="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Akun</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-1 text-sm">
                        {client.users.map((user) => (
                            <div key={user.id}>
                                {user.name} · {user.username ?? user.email}
                            </div>
                        ))}
                        {client.users.length === 0 && <div>Belum ada akun.</div>}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Produk</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-1 text-sm">
                        {client.products.map((product) => (
                            <div key={product.id}>{product.name}</div>
                        ))}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Campaign</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-1 text-sm">
                        {client.campaigns.map((campaign) => (
                            <Link key={campaign.id} href={route('campaigns.show', campaign.id)} className="block underline">
                                {campaign.name} ({campaign.status})
                            </Link>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
