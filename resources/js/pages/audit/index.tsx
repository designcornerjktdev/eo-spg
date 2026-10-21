import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface LogRow {
    id: number;
    action: string;
    auditable_type: string;
    auditable_id: number;
    created_at: string;
    user?: { name: string } | null;
}

export default function AuditIndex({ logs }: { logs: { data: LogRow[]; links: { url: string | null; label: string; active: boolean }[] } }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Audit log', href: '/audit' }]}>
            <Head title="Audit log" />
            <PageHeader title="Audit log" description="Perubahan form, assignment, dan void submission." />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Waktu</th>
                            <th className="px-3 py-2 text-left">Pengguna</th>
                            <th className="px-3 py-2 text-left">Aksi</th>
                            <th className="px-3 py-2 text-left">Objek</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.data.map((log) => (
                            <tr key={log.id} className="border-t">
                                <td className="px-3 py-2">{log.created_at}</td>
                                <td className="px-3 py-2">{log.user?.name ?? '-'}</td>
                                <td className="px-3 py-2">{log.action}</td>
                                <td className="px-3 py-2">
                                    {log.auditable_type} #{log.auditable_id}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination links={logs.links} />
        </AppLayout>
    );
}
