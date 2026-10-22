import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Client {
    id: number;
    name: string;
    code: string;
    notes: string | null;
}

export default function ClientEdit({ client }: { client: Client }) {
    const { data, setData, put, processing, errors } = useForm({
        name: client.name,
        code: client.code,
        notes: client.notes ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(route('clients.update', client.id));
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Client', href: '/clients' },
                { title: client.name, href: route('clients.show', client.id) },
            ]}
        >
            <Head title={`Ubah ${client.name}`} />
            <PageHeader title="Ubah client" />
            <form onSubmit={submit} className="grid max-w-xl gap-4">
                <div className="grid gap-2">
                    <Label>Nama</Label>
                    <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                    <InputError message={errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label>Kode</Label>
                    <Input value={data.code} onChange={(e) => setData('code', e.target.value)} />
                    <InputError message={errors.code} />
                </div>
                <div className="grid gap-2">
                    <Label>Catatan</Label>
                    <Textarea value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                </div>
                <Button disabled={processing}>Simpan</Button>
            </form>
        </AppLayout>
    );
}
