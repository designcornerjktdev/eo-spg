import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Row {
    id: number;
    name: string;
    email: string;
    role: string;
    client?: { name: string } | null;
}

export default function UsersIndex({ users }: { users: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] } }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Pengguna', href: '/users' }]}>
            <Head title="Pengguna" />
            <PageHeader title="Akun Client & SPG" action={{ href: route('users.create'), label: 'Tambah akun' }} />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Nama</th>
                            <th className="px-3 py-2 text-left">Email</th>
                            <th className="px-3 py-2 text-left">Role</th>
                            <th className="px-3 py-2 text-left">Client</th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.data.map((user) => (
                            <tr key={user.id} className="border-t">
                                <td className="px-3 py-2">{user.name}</td>
                                <td className="px-3 py-2">{user.email}</td>
                                <td className="px-3 py-2">{user.role}</td>
                                <td className="px-3 py-2">{user.client?.name ?? '-'}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination links={users.links} />
        </AppLayout>
    );
}
