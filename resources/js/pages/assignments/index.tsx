import FilterBar from '@/components/filter-bar';
import IconAction from '@/components/icon-action';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Head } from '@inertiajs/react';
import { Pencil } from 'lucide-react';

interface Row {
    id: number;
    starts_at: string;
    ends_at: string;
    status: string;
    campaign: { name: string };
    location: { name: string };
    user: { name: string };
}

const statusLabels: Record<string, string> = { scheduled: 'Terjadwal', active: 'Berjalan', done: 'Selesai', cancelled: 'Dibatalkan' };

function formatTime(value: string) {
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export default function AssignmentsIndex({
    assignments,
    filters,
    campaigns,
}: {
    assignments: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { search?: string; status?: string | null; campaign_id?: number | null };
    campaigns: { id: number; name: string }[];
}) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Penugasan SPG', href: '/assignments' }]}>
            <Head title="Penugasan SPG" />
            <PageHeader
                title="Penugasan SPG"
                description="Jadwal SPG per lokasi campaign. Satu SPG tidak bisa punya dua jadwal yang bertabrakan."
                action={{ href: route('assignments.create'), label: 'Tambah penugasan' }}
            />
            <FilterBar
                url={route('assignments.index')}
                filters={filters}
                searchPlaceholder="Cari nama SPG atau lokasi"
                selects={[
                    { name: 'campaign_id', label: 'Semua campaign', options: campaigns.map((c) => ({ value: c.id, label: c.name })) },
                    { name: 'status', label: 'Semua status', options: Object.entries(statusLabels).map(([value, label]) => ({ value, label })) },
                ]}
            />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">SPG</th>
                            <th className="px-3 py-2 text-left">Campaign</th>
                            <th className="px-3 py-2 text-left">Lokasi</th>
                            <th className="px-3 py-2 text-left">Jadwal</th>
                            <th className="px-3 py-2 text-left">Status</th>
                            <th className="px-3 py-2 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {assignments.data.map((assignment) => (
                            <tr key={assignment.id} className={cn('border-t', assignment.status === 'cancelled' && 'text-muted-foreground')}>
                                <td className="px-3 py-2">{assignment.user.name}</td>
                                <td className="px-3 py-2">{assignment.campaign.name}</td>
                                <td className="px-3 py-2">{assignment.location.name}</td>
                                <td className="px-3 py-2 whitespace-nowrap">
                                    {formatTime(assignment.starts_at)} — {formatTime(assignment.ends_at)}
                                </td>
                                <td className="px-3 py-2">
                                    <Badge variant={assignment.status === 'cancelled' ? 'outline' : 'secondary'}>
                                        {statusLabels[assignment.status] ?? assignment.status}
                                    </Badge>
                                </td>
                                <td className="px-3 py-1">
                                    <IconAction icon={Pencil} label="Edit penugasan" href={route('assignments.edit', assignment.id)} />
                                </td>
                            </tr>
                        ))}
                        {assignments.data.length === 0 && (
                            <tr>
                                <td colSpan={6} className="text-muted-foreground px-3 py-8 text-center">
                                    Tidak ada penugasan yang cocok.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={assignments.links} />
        </AppLayout>
    );
}
