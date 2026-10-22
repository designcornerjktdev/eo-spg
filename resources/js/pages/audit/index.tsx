import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, label } from '@/lib/format';
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
            <PageHeader title="Audit log" description="History of important changes by admins and SPGs." />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Time</th>
                            <th className="px-3 py-2 text-left">Users</th>
                            <th className="px-3 py-2 text-left">Actions</th>
                            <th className="px-3 py-2 text-left">Object</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.data.map((log) => (
                            <tr key={log.id} className="border-t">
                                <td className="px-3 py-2 whitespace-nowrap">{formatDateTime(log.created_at)}</td>
                                <td className="px-3 py-2">{log.user?.name ?? '-'}</td>
                                <td className="px-3 py-2">{label('auditAction', log.action)}</td>
                                <td className="px-3 py-2">
                                    {label('auditableType', log.auditable_type)} <span className="text-muted-foreground">#{log.auditable_id}</span>
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
