import FilterBar from '@/components/filter-bar';
import IconAction from '@/components/icon-action';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { formatDateRange, label, labelOptions } from '@/lib/format';
import { SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Eye, Pencil } from 'lucide-react';

interface CampaignRow {
    id: number;
    name: string;
    status: string;
    starts_on: string;
    ends_on: string;
    viewers_count: number;
    locations_count: number;
    submissions_count: number;
}

export default function CampaignsIndex({
    campaigns,
    filters,
}: {
    campaigns: { data: CampaignRow[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { search?: string; status?: string | null };
}) {
    const canEdit = usePage<SharedData>().props.auth.user?.role !== 'client';

    return (
        <AppLayout breadcrumbs={[{ title: 'Campaign', href: '/campaigns' }]}>
            <Head title="Campaign" />
            <PageHeader
                title="Campaign"
                description="Campaigns stay in draft until activated."
                action={canEdit ? { href: route('campaigns.create'), label: 'New campaign' } : undefined}
            />
            <FilterBar
                url={route('campaigns.index')}
                filters={filters}
                searchPlaceholder="Search campaign name"
                selects={[{ name: 'status', label: 'All statuses', options: labelOptions('campaignStatus') }]}
            />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Name</th>
                            <th className="px-3 py-2 text-left">Client access</th>
                            <th className="px-3 py-2 text-left">Period</th>
                            <th className="px-3 py-2 text-left">Status</th>
                            <th className="px-3 py-2 text-left">Location</th>
                            <th className="px-3 py-2 text-left">Entries</th>
                            <th className="px-3 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {campaigns.data.map((campaign) => (
                            <tr key={campaign.id} className="border-t">
                                <td className="px-3 py-2">{campaign.name}</td>
                                <td className="px-3 py-2">{campaign.viewers_count === 1 ? '1 account' : `${campaign.viewers_count} accounts`}</td>
                                <td className="px-3 py-2 whitespace-nowrap">{formatDateRange(campaign.starts_on, campaign.ends_on)}</td>
                                <td className="px-3 py-2">
                                    <Badge variant={campaign.status === 'active' ? 'default' : 'secondary'}>
                                        {label('campaignStatus', campaign.status)}
                                    </Badge>
                                </td>
                                <td className="px-3 py-2">{campaign.locations_count}</td>
                                <td className="px-3 py-2">{campaign.submissions_count}</td>
                                <td className="px-3 py-1">
                                    <div className="flex gap-1">
                                        <IconAction icon={Eye} label="View details" href={route('campaigns.show', campaign.id)} />
                                        {canEdit && <IconAction icon={Pencil} label="Edit campaign" href={route('campaigns.edit', campaign.id)} />}
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {campaigns.data.length === 0 && (
                            <tr>
                                <td colSpan={7} className="text-muted-foreground px-3 py-8 text-center">
                                    No matching campaigns.
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
