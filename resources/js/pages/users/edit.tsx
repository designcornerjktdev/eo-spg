import CampaignAccessPicker, { type CampaignOption } from '@/components/campaign-access-picker';
import DangerZone from '@/components/danger-zone';
import FormPage from '@/components/form-page';
import InputError from '@/components/input-error';
import Link from '@/components/link';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface EditableUser {
    id: number;
    name: string;
    username: string | null;
    email: string | null;
    role: 'client' | 'admin';
    campaign_ids: number[];
    is_active: boolean;
}

export default function UserEdit({ user, campaigns }: { user: EditableUser; campaigns: CampaignOption[] }) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        username: user.username ?? '',
        ...(user.role === 'admin' ? { email: user.email ?? '' } : {}),
        campaign_ids: user.campaign_ids,
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
                <PageHeader
                    title="Edit account"
                    description={user.role === 'admin' ? 'Admin account: sees and runs every campaign' : 'Client account'}
                />

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
                    {user.role === 'admin' && (
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                autoComplete="off"
                                value={data.email ?? ''}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                            <p className="text-muted-foreground text-xs">
                                Admins can sign in with this email or the username, and reset a forgotten password themselves.
                            </p>
                            <InputError message={errors.email} />
                        </div>
                    )}
                    {user.role === 'client' && (
                        <CampaignAccessPicker
                            campaigns={campaigns}
                            value={data.campaign_ids}
                            onChange={(ids) => setData('campaign_ids', ids)}
                            error={errors.campaign_ids}
                        />
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
