import CampaignAccessPicker, { type CampaignOption } from '@/components/campaign-access-picker';
import FormPage from '@/components/form-page';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { PasswordInput } from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function UserCreate({ campaigns }: { campaigns: CampaignOption[] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        username: '',
        password: '',
        role: 'client',
        campaign_ids: [] as number[],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
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
                    <CampaignAccessPicker
                        campaigns={campaigns}
                        value={data.campaign_ids}
                        onChange={(ids) => setData('campaign_ids', ids)}
                        error={errors.campaign_ids}
                    />
                    <Button disabled={processing}>Save</Button>
                </form>
            </FormPage>
        </AppLayout>
    );
}
