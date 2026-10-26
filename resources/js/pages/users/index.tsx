import IconAction from '@/components/icon-action';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { PasswordInput } from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { label } from '@/lib/format';
import { cn } from '@/lib/utils';
import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, Pencil } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface Row {
    id: number;
    name: string;
    username: string | null;
    email: string | null;
    role: string;
    is_active: boolean;
    campaigns: { id: number; name: string }[];
}

interface Filters {
    search?: string;
    role?: string;
    status?: string;
}

export default function UsersIndex({
    users,
    filters,
}: {
    users: { data: Row[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Filters;
}) {
    const [resetting, setResetting] = useState<Row | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (next: Filters) => {
        router.get(route('users.index'), { ...filters, ...next }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Users', href: '/users' }]}>
            <Head title="Users" />
            <PageHeader
                title="Accounts"
                description="Admins run campaigns, forms and entries; clients see results of their granted campaigns. Admins sign in with email or username and can reset their own password; clients use a username and ask you to reset it."
                action={{ href: route('users.create'), label: 'New account' }}
            />

            <form
                className="flex flex-wrap gap-2"
                onSubmit={(e) => {
                    e.preventDefault();
                    applyFilters({ search });
                }}
            >
                <Input
                    className="w-full sm:w-64"
                    placeholder="Search name, username or email"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                />
                <select
                    aria-label="Filter role"
                    className="border-input bg-background h-9 rounded-md border px-2 text-sm"
                    value={filters.role ?? ''}
                    onChange={(e) => applyFilters({ role: e.target.value })}
                >
                    <option value="">All roles</option>
                    <option value="admin">Admin</option>
                    <option value="client">Client</option>
                </select>
                <select
                    aria-label="Filter status"
                    className="border-input bg-background h-9 rounded-md border px-2 text-sm"
                    value={filters.status ?? ''}
                    onChange={(e) => applyFilters({ status: e.target.value })}
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <Button type="submit" variant="outline">
                    Search
                </Button>
            </form>

            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Name</th>
                            <th className="px-3 py-2 text-left">Username</th>
                            <th className="px-3 py-2 text-left">Role</th>
                            <th className="px-3 py-2 text-left">Campaign access</th>
                            <th className="px-3 py-2 text-left">Status</th>
                            <th className="px-3 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.data.map((user) => (
                            <tr key={user.id} className={cn('border-t', !user.is_active && 'text-muted-foreground')}>
                                <td className="px-3 py-2">{user.name}</td>
                                <td className="px-3 py-2">
                                    <span className="font-mono">{user.username ?? '-'}</span>
                                    {user.email && <span className="text-muted-foreground block text-xs">{user.email}</span>}
                                </td>
                                <td className="px-3 py-2">{label('role', user.role)}</td>
                                <td className="max-w-72 truncate px-3 py-2" title={user.campaigns.map((c) => c.name).join(', ')}>
                                    {user.role === 'admin' ? (
                                        <span className="text-muted-foreground">All campaigns</span>
                                    ) : user.campaigns.length ? (
                                        user.campaigns.map((c) => c.name).join(', ')
                                    ) : (
                                        <span className="text-muted-foreground">None</span>
                                    )}
                                </td>
                                <td className="px-3 py-2">
                                    {user.is_active ? <Badge variant="secondary">Active</Badge> : <Badge variant="outline">Inactive</Badge>}
                                </td>
                                <td className="px-3 py-1">
                                    <div className="flex gap-1">
                                        <IconAction icon={Pencil} label="Edit account" href={route('users.edit', user.id)} />
                                        <IconAction icon={KeyRound} label="Reset password" onClick={() => setResetting(user)} />
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {users.data.length === 0 && (
                            <tr>
                                <td colSpan={6} className="text-muted-foreground px-3 py-8 text-center">
                                    No matching accounts.
                                </td>
                            </tr>
                        )}
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
                        New password for {user?.name} ({user?.username}). Give this password directly to the account owner.
                    </DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="new-password">New password</Label>
                        <PasswordInput
                            id="new-password"
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <InputError message={errors.password} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="new-password-confirmation">Repeat new password</Label>
                        <PasswordInput
                            id="new-password-confirmation"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={close}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Save password
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
