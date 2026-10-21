import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

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

export default function CampaignsIndex({
    campaigns,
}: {
    campaigns: { data: CampaignRow[]; links: { url: string | null; label: string; active: boolean }[] };
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
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        {campaigns.data.map((campaign) => (
                            <tr key={campaign.id} className="border-t">
                                <td className="px-3 py-2">{campaign.name}</td>
                                <td className="px-3 py-2">{campaign.client.name}</td>
                                <td className="px-3 py-2">
                                    {campaign.starts_on} — {campaign.ends_on}
                                </td>
                                <td className="px-3 py-2 capitalize">{campaign.status}</td>
                                <td className="px-3 py-2">{campaign.locations_count}</td>
                                <td className="px-3 py-2">{campaign.submissions_count}</td>
                                <td className="px-3 py-2 text-right">
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={route('campaigns.show', campaign.id)}>Lihat</Link>
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination links={campaigns.links} />
        </AppLayout>
    );
}
