import FilterBar from '@/components/filter-bar';
import IconAction from '@/components/icon-action';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Eye, Pencil } from 'lucide-react';

interface CampaignRow {
    id: number;
    name: string;
    status: string;
    starts_on: string;
    ends_on: string;
    client: { name: string };
    locations_count: number;
    submissions_count: number;
}

const statusLabels: Record<string, string> = { draft: 'Draf', active: 'Aktif', closed: 'Ditutup' };

export default function CampaignsIndex({
    campaigns,
    filters,
    clients,
}: {
    campaigns: { data: CampaignRow[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { search?: string; status?: string | null; client_id?: number | null };
    clients: { id: number; name: string }[];
}) {
    const canEdit = usePage<SharedData>().props.auth.user?.role === 'superadmin';

    return (
        <AppLayout breadcrumbs={[{ title: 'Campaign', href: '/campaigns' }]}>
            <Head title="Campaign" />
            <PageHeader
                title="Campaign"
                description="Campaign tetap draf sampai diaktifkan."
                action={canEdit ? { href: route('campaigns.create'), label: 'Tambah campaign' } : undefined}
            />
            <FilterBar
                url={route('campaigns.index')}
                filters={filters}
                searchPlaceholder="Cari nama campaign"
                selects={[
                    { name: 'status', label: 'Semua status', options: Object.entries(statusLabels).map(([value, label]) => ({ value, label })) },
                    ...(canEdit ? [{ name: 'client_id', label: 'Semua client', options: clients.map((c) => ({ value: c.id, label: c.name })) }] : []),
                ]}
            />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Nama</th>
                            <th className="px-3 py-2 text-left">Client</th>
                            <th className="px-3 py-2 text-left">Periode</th>
                            <th className="px-3 py-2 text-left">Status</th>
                            <th className="px-3 py-2 text-left">Lokasi</th>
                            <th className="px-3 py-2 text-left">Data</th>
                            <th className="px-3 py-2 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {campaigns.data.map((campaign) => (
                            <tr key={campaign.id} className="border-t">
                                <td className="px-3 py-2">{campaign.name}</td>
                                <td className="px-3 py-2">{campaign.client.name}</td>
                                <td className="px-3 py-2 whitespace-nowrap">
                                    {campaign.starts_on?.slice(0, 10)} — {campaign.ends_on?.slice(0, 10)}
                                </td>
                                <td className="px-3 py-2">
                                    <Badge variant={campaign.status === 'active' ? 'default' : 'secondary'}>
                                        {statusLabels[campaign.status] ?? campaign.status}
                                    </Badge>
                                </td>
                                <td className="px-3 py-2">{campaign.locations_count}</td>
                                <td className="px-3 py-2">{campaign.submissions_count}</td>
                                <td className="px-3 py-1">
                                    <div className="flex gap-1">
                                        <IconAction icon={Eye} label="Lihat detail" href={route('campaigns.show', campaign.id)} />
                                        {canEdit && <IconAction icon={Pencil} label="Edit campaign" href={route('campaigns.edit', campaign.id)} />}
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {campaigns.data.length === 0 && (
                            <tr>
                                <td colSpan={7} className="text-muted-foreground px-3 py-8 text-center">
                                    Tidak ada campaign yang cocok.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={campaigns.links} />
        </AppLayout>
    );
}
