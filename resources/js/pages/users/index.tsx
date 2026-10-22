import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Row {
    id: number;
    name: string;
    username: string | null;
    role: string;
    client?: { name: string } | null;
}

export default function UsersIndex({ users }: { users: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] } }) {
    const [resetting, setResetting] = useState<Row | null>(null);

    return (
        <AppLayout breadcrumbs={[{ title: 'Pengguna', href: '/users' }]}>
            <Head title="Pengguna" />
            <PageHeader
                title="Akun Client & SPG"
                description="Client dan SPG login dengan username. Jika lupa password, reset dari sini."
                action={{ href: route('users.create'), label: 'Tambah akun' }}
            />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Nama</th>
                            <th className="px-3 py-2 text-left">Username</th>
                            <th className="px-3 py-2 text-left">Role</th>
                            <th className="px-3 py-2 text-left">Client</th>
                            <th className="px-3 py-2 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.data.map((user) => (
                            <tr key={user.id} className="border-t">
                                <td className="px-3 py-2">{user.name}</td>
                                <td className="px-3 py-2 font-mono">{user.username ?? '-'}</td>
                                <td className="px-3 py-2">{user.role}</td>
                                <td className="px-3 py-2">{user.client?.name ?? '-'}</td>
                                <td className="px-3 py-1">
                                    <Button size="sm" variant="ghost" onClick={() => setResetting(user)}>
                                        Reset password
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination links={users.links} />

            <ResetPasswordDialog user={resetting} onClose={() => setResetting(null)} />
        </AppLayout>
    );
}

function ResetPasswordDialog({ user, onClose }: { user: Row | null; onClose: () => void }) {
    const { data, setData, put, processing, errors, reset } = useForm({ password: '', password_confirmation: '' });

    const close = () => {
        reset();
        onClose();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!user) return;
        put(route('users.password', user.id), { preserveScroll: true, onSuccess: close });
    };

    return (
        <Dialog open={!!user} onOpenChange={(open) => !open && close()}>
            <DialogContent className="max-w-[calc(100%-1rem)] md:max-w-md">
                <DialogHeader className="text-left">
                    <DialogTitle>Reset password</DialogTitle>
                    <DialogDescription>
                        Password baru untuk {user?.name} ({user?.username}). Berikan password ini langsung ke yang bersangkutan.
                    </DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="new-password">Password baru</Label>
                        <Input
                            id="new-password"
                            type="password"
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <InputError message={errors.password} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="new-password-confirmation">Ulangi password baru</Label>
                        <Input
                            id="new-password-confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={close}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Simpan password
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
