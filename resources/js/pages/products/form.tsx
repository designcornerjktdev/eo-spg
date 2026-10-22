import FormPage from '@/components/form-page';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Product {
    id: number;
    client_id: number;
    name: string;
    sku: string | null;
    description: string | null;
}

export default function ProductForm({
    product,
    clients,
    footer,
}: {
    product?: Product;
    clients: { id: number; name: string }[];
    footer?: React.ReactNode;
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        client_id: product?.client_id?.toString() ?? '',
        name: product?.name ?? '',
        sku: product?.sku ?? '',
        description: product?.description ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (product) {
            put(route('products.update', product.id));
        } else {
            post(route('products.store'));
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Products', href: '/products' },
                { title: product ? 'Edit' : 'New', href: '#' },
            ]}
        >
            <Head title={product ? 'Edit product' : 'New product'} />
            <FormPage>
                <PageHeader title={product ? 'Edit product' : 'New product'} />
                <form onSubmit={submit} className="grid gap-4 rounded-xl border p-4 sm:p-6">
                    <div className="grid gap-2">
                        <Label>Client</Label>
                        <select
                            className="border-input h-9 rounded-md border px-2"
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
                    <div className="grid gap-2">
                        <Label>Name</Label>
                        <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label>SKU</Label>
                        <Input value={data.sku} onChange={(e) => setData('sku', e.target.value)} />
                    </div>
                    <div className="grid gap-2">
                        <Label>Description</Label>
                        <Textarea value={data.description} onChange={(e) => setData('description', e.target.value)} />
                    </div>
                    <Button disabled={processing}>Save</Button>
                </form>
                {footer}
            </FormPage>
        </AppLayout>
    );
}
