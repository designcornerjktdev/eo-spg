import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Client {
    id: number;
    name: string;
    code: string;
    notes: string | null;
}

export default function ClientEdit({ client, deleteBlocker }: { client: Client; deleteBlocker: string | null }) {
    const { data, setData, put, processing, errors } = useForm({
        name: client.name,
        code: client.code,
        notes: client.notes ?? '',
    });
    const [confirmDelete, setConfirmDelete] = useState(false);
    const pageErrors = usePage().props.errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put(route('clients.update', client.id));
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Client', href: '/clients' },
                { title: client.name, href: route('clients.show', client.id) },
                { title: 'Edit', href: route('clients.edit', client.id) },
            ]}
        >
            <Head title={`Edit ${client.name}`} />
            <PageHeader title="Edit client" />
            <form onSubmit={submit} className="grid max-w-xl gap-4">
                <div className="grid gap-2">
                    <Label htmlFor="name">Nama</Label>
                    <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                    <InputError message={errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="code">Kode</Label>
                    <Input id="code" value={data.code} onChange={(e) => setData('code', e.target.value)} />
                    <InputError message={errors.code} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="notes">Catatan</Label>
                    <Textarea id="notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                </div>
                <div className="flex gap-2">
                    <Button disabled={processing}>Simpan</Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href={route('clients.show', client.id)}>Batal</Link>
                    </Button>
                </div>
            </form>

            <div className="max-w-xl space-y-2 rounded-lg border border-red-200 p-4 dark:border-red-900">
                <h2 className="font-semibold">Hapus client</h2>
                {deleteBlocker ? (
                    <p className="text-muted-foreground text-sm">{deleteBlocker}</p>
                ) : (
                    <>
                        <p className="text-muted-foreground text-sm">
                            Client belum punya campaign atau produk. Menghapusnya juga menonaktifkan semua akun login client ini.
                        </p>
                        <Button variant="destructive" onClick={() => setConfirmDelete(true)}>
                            Hapus client
                        </Button>
                    </>
                )}
                <InputError message={pageErrors.client} />
            </div>

            <Dialog open={confirmDelete} onOpenChange={setConfirmDelete}>
                <DialogContent className="max-w-[calc(100%-1rem)] md:max-w-md">
                    <DialogHeader className="text-left">
                        <DialogTitle>Hapus client {client.name}?</DialogTitle>
                        <DialogDescription>Semua akun login client ini akan dinonaktifkan.</DialogDescription>
                    </DialogHeader>
                    <div className="flex justify-end gap-2">
                        <Button variant="outline" onClick={() => setConfirmDelete(false)}>
                            Kembali
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => router.delete(route('clients.destroy', client.id), { onFinish: () => setConfirmDelete(false) })}
                        >
                            Hapus client
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
