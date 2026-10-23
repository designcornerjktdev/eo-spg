import DangerZone from '@/components/danger-zone';
import FormPage from '@/components/form-page';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface EditableUser {
    id: number;
    name: string;
    username: string | null;
    role: 'client';
    client_id: number | null;
    is_active: boolean;
}

export default function UserEdit({ user, clients }: { user: EditableUser; clients: { id: number; name: string }[] }) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        username: user.username ?? '',
        client_id: user.client_id ? String(user.client_id) : '',
        is_active: user.is_active,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(route('users.update', user.id));
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Users', href: '/users' },
                { title: user.name, href: route('users.edit', user.id) },
            ]}
        >
            <Head title={`Edit ${user.name}`} />
            <FormPage>
                <PageHeader title="Edit account" description="Client account" />

                <form onSubmit={submit} className="grid gap-4 rounded-xl border p-4 sm:p-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="username">Username</Label>
                        <Input
                            id="username"
                            autoCapitalize="none"
                            value={data.username}
                            onChange={(e) => setData('username', e.target.value.toLowerCase())}
                        />
                        <p className="text-muted-foreground text-xs">Changing the username means this account must sign in with the new one.</p>
                        <InputError message={errors.username} />
                    </div>
                    {user.role === 'client' && (
                        <div className="grid gap-2">
                            <Label htmlFor="client">Client</Label>
                            <select
                                id="client"
                                className="border-input bg-background h-9 rounded-md border px-2"
                                value={data.client_id}
                                onChange={(e) => setData('client_id', e.target.value)}
                            >
                                <option value="">Select a client</option>
                                {clients.map((client) => (
                                    <option key={client.id} value={client.id}>
                                        {client.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.client_id} />
                        </div>
                    )}
                    <label className="flex items-start gap-2 text-sm">
                        <Checkbox checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked === true)} />
                        <span>
                            Account active
                            <span className="text-muted-foreground block text-xs">
                                Inactive accounts cannot sign in and are signed out of every device immediately.
                            </span>
                        </span>
                    </label>
                    <div className="flex gap-2">
                        <Button disabled={processing}>Save</Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('users.index')}>Cancel</Link>
                        </Button>
                    </div>
                </form>

                <DangerZone
                    title="Delete account"
                    description="The client will no longer be able to sign in. Campaign data is not affected."
                    confirmTitle={`Delete account ${user.name}?`}
                    deleteUrl={route('users.destroy', user.id)}
                    blocker={null}
                    errorKey="user"
                />
            </FormPage>
        </AppLayout>
    );
}
