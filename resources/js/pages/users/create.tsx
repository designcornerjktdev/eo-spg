import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function UserCreate({ clients }: { clients: { id: number; name: string }[] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        role: 'spg',
        client_id: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('users.store'));
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Pengguna', href: '/users' }, { title: 'Tambah', href: '#' }]}>
            <Head title="Tambah akun" />
            <PageHeader title="Tambah akun" />
            <form onSubmit={submit} className="grid max-w-xl gap-4">
                <div className="grid gap-2">
                    <Label>Nama</Label>
                    <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                    <InputError message={errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label>Email</Label>
                    <Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                    <InputError message={errors.email} />
                </div>
                <div className="grid gap-2">
                    <Label>Password</Label>
                    <Input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                    <InputError message={errors.password} />
                </div>
                <div className="grid gap-2">
                    <Label>Role</Label>
                    <select className="border-input h-9 rounded-md border px-2" value={data.role} onChange={(e) => setData('role', e.target.value)}>
                        <option value="spg">SPG</option>
                        <option value="client">Client</option>
                    </select>
                </div>
                {data.role === 'client' && (
                    <div className="grid gap-2">
                        <Label>Client</Label>
                        <select className="border-input h-9 rounded-md border px-2" value={data.client_id} onChange={(e) => setData('client_id', e.target.value)}>
                            <option value="">Pilih client</option>
                            {clients.map((client) => (
                                <option key={client.id} value={client.id}>
                                    {client.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.client_id} />
                    </div>
                )}
                <Button disabled={processing}>Simpan</Button>
            </form>
        </AppLayout>
    );
}
