import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

interface Row {
    id: number;
    starts_at: string;
    ends_at: string;
    status: string;
    campaign: { name: string };
    location: { name: string };
    user: { name: string };
}

export default function AssignmentsIndex({
    assignments,
}: {
    assignments: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
}) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Assignment', href: '/assignments' }]}>
            <Head title="Assignment" />
            <PageHeader title="Assignment SPG" action={{ href: route('assignments.create'), label: 'Tambah assignment' }} />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">SPG</th>
                            <th className="px-3 py-2 text-left">Campaign</th>
                            <th className="px-3 py-2 text-left">Lokasi</th>
                            <th className="px-3 py-2 text-left">Jadwal</th>
                            <th className="px-3 py-2 text-left">Status</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        {assignments.data.map((assignment) => (
                            <tr key={assignment.id} className="border-t">
                                <td className="px-3 py-2">{assignment.user.name}</td>
                                <td className="px-3 py-2">{assignment.campaign.name}</td>
                                <td className="px-3 py-2">{assignment.location.name}</td>
                                <td className="px-3 py-2">
                                    {assignment.starts_at} — {assignment.ends_at}
                                </td>
                                <td className="px-3 py-2">{assignment.status}</td>
                                <td className="px-3 py-2 text-right">
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={route('assignments.edit', assignment.id)}>Ubah</Link>
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination links={assignments.links} />
        </AppLayout>
    );
}
