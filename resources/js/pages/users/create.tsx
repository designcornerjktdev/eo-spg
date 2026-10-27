import CampaignAccessPicker, { type CampaignOption } from '@/components/campaign-access-picker';
import FormPage from '@/components/form-page';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { PasswordInput } from '@/components/password-input';
import { SimpleSelect } from '@/components/simple-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function UserCreate({ campaigns }: { campaigns: CampaignOption[] }) {
    const { data, setData, post, transform, processing, errors } = useForm({
        name: '',
        username: '',
        password: '',
        role: 'client',
        email: '',
        campaign_ids: [] as number[],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        // Clients have no email; drop a value typed before switching the role.
        transform((payload) => ({ ...payload, email: payload.role === 'admin' ? payload.email : null }));
        post(route('users.store'));
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Users', href: '/users' },
                { title: 'New', href: '#' },
            ]}
        >
            <Head title="New account" />
            <FormPage>
                <PageHeader title="New account" />
                <form onSubmit={submit} className="grid gap-4 rounded-xl border p-4 sm:p-6">
                    <div className="grid gap-2">
                        <Label>Name</Label>
                        <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="username">Username</Label>
                        <Input
                            id="username"
                            autoCapitalize="none"
                            placeholder="lowercase letters, numbers, dot, - or _"
                            value={data.username}
                            onChange={(e) => setData('username', e.target.value.toLowerCase())}
                        />
                        <InputError message={errors.username} />
                    </div>
                    <div className="grid gap-2">
                        <Label>Password</Label>
                        <PasswordInput value={data.password} onChange={(e) => setData('password', e.target.value)} />
                        <InputError message={errors.password} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="role">Role</Label>
                        <SimpleSelect
                            id="role"
                            value={data.role}
                            onChange={(role) => setData('role', role)}
                            options={[
                                { value: 'client', label: 'Client: dashboard and entries of granted campaigns' },
                                { value: 'admin', label: 'Admin: runs campaigns, forms and entries' },
                            ]}
                        />
                        <InputError message={errors.role} />
                    </div>
                    {data.role === 'admin' && (
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input id="email" type="email" autoComplete="off" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                            <p className="text-muted-foreground text-xs">
                                Admins can sign in with this email or the username, and reset a forgotten password themselves.
                            </p>
                            <InputError message={errors.email} />
                        </div>
                    )}
                    {/* Admins see every campaign, so access is only picked for clients. */}
                    {data.role === 'client' && (
                        <CampaignAccessPicker
                            campaigns={campaigns}
                            value={data.campaign_ids}
                            onChange={(ids) => setData('campaign_ids', ids)}
                            error={errors.campaign_ids}
                        />
                    )}
                    <Button disabled={processing}>Save</Button>
                </form>
            </FormPage>
        </AppLayout>
    );
}
