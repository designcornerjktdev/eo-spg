import FormPage from '@/components/form-page';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { PasswordInput } from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function ClientCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        code: '',
        notes: '',
        account_name: '',
        account_username: '',
        account_password: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('clients.store'));
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Client', href: '/clients' },
                { title: 'New', href: '/clients/create' },
            ]}
        >
            <Head title="New client" />
            <FormPage>
                <PageHeader title="New client" description="Create a client company and, optionally, its sign-in account." />
                <form onSubmit={submit} className="grid gap-4 rounded-xl border p-4 sm:p-6">
                    <Field label="Name" error={errors.name}>
                        <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                    </Field>
                    <Field label="Code" error={errors.code}>
                        <Input value={data.code} onChange={(e) => setData('code', e.target.value)} />
                    </Field>
                    <Field label="Notes" error={errors.notes}>
                        <Textarea value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                    </Field>
                    <div className="pt-2 font-medium">Client account (optional)</div>
                    <Field label="Account name" error={errors.account_name}>
                        <Input value={data.account_name} onChange={(e) => setData('account_name', e.target.value)} />
                    </Field>
                    <Field label="Account username" error={errors.account_username}>
                        <Input
                            value={data.account_username}
                            autoCapitalize="none"
                            placeholder="lowercase letters, numbers, dot, - or _"
                            onChange={(e) => setData('account_username', e.target.value.toLowerCase())}
                        />
                    </Field>
                    <Field label="Account password" error={errors.account_password}>
                        <PasswordInput value={data.account_password} onChange={(e) => setData('account_password', e.target.value)} />
                    </Field>
                    <Button disabled={processing}>Save</Button>
                </form>
            </FormPage>
        </AppLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
