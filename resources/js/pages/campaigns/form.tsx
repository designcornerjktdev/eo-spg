import FormPage from '@/components/form-page';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent, Suspense, lazy } from 'react';

interface Location {
    id?: number;
    name: string;
    area: string | null;
    address: string | null;
    latitude?: number | null;
    longitude?: number | null;
}

interface Campaign {
    id: number;
    client_id: number;
    name: string;
    starts_on: string;
    ends_on: string;
    notes: string | null;
    mask_identity_for_client: boolean;
    products: { id: number }[];
    locations: Location[];
}

const LocationPicker = lazy(() => import('@/components/location-picker'));

type FormLocation = {
    id?: number;
    name: string;
    area: string;
    address: string;
    latitude: number | null;
    longitude: number | null;
};

const EMPTY_LOCATION: FormLocation = { name: '', area: '', address: '', latitude: null, longitude: null };

export default function CampaignForm({
    campaign,
    clients,
    products,
    footer,
}: {
    footer?: React.ReactNode;
    campaign?: Campaign;
    clients: { id: number; name: string }[];
    products: { id: number; name: string; client_id: number }[];
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        client_id: campaign?.client_id?.toString() ?? '',
        name: campaign?.name ?? '',
        starts_on: campaign?.starts_on?.slice(0, 10) ?? '',
        ends_on: campaign?.ends_on?.slice(0, 10) ?? '',
        notes: campaign?.notes ?? '',
        mask_identity_for_client: campaign?.mask_identity_for_client ?? true,
        product_ids: (campaign?.products ?? []).map((product) => product.id.toString()),
        locations: campaign?.locations?.length
            ? campaign.locations.map((location): FormLocation => ({
                  id: location.id,
                  name: location.name,
                  area: location.area ?? '',
                  address: location.address ?? '',
                  latitude: location.latitude ?? null,
                  longitude: location.longitude ?? null,
              }))
            : [{ ...EMPTY_LOCATION }],
    });

    const clientProducts = products.filter((product) => product.client_id.toString() === data.client_id);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (campaign) {
            put(route('campaigns.update', campaign.id));
        } else {
            post(route('campaigns.store'));
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Campaign', href: '/campaigns' },
                { title: campaign ? 'Ubah' : 'Tambah', href: '#' },
            ]}
        >
            <Head title={campaign ? 'Ubah campaign' : 'Tambah campaign'} />
            <FormPage width="lg">
                <PageHeader title={campaign ? 'Ubah campaign' : 'Tambah campaign'} />
                <form onSubmit={submit} className="grid gap-4 rounded-xl border p-4 sm:p-6">
                    <div className="grid gap-2">
                        <Label>Client</Label>
                        <select
                            className="border-input h-9 rounded-md border px-2"
                            value={data.client_id}
                            onChange={(e) => setData('client_id', e.target.value)}
                        >
                            <option value="">Pilih client</option>
                            {clients.map((client) => (
                                <option key={client.id} value={client.id}>
                                    {client.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.client_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label>Nama</Label>
                        <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Mulai</Label>
                            <Input type="date" value={data.starts_on} onChange={(e) => setData('starts_on', e.target.value)} />
                            <InputError message={errors.starts_on} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Selesai</Label>
                            <Input type="date" value={data.ends_on} onChange={(e) => setData('ends_on', e.target.value)} />
                            <InputError message={errors.ends_on} />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            checked={data.mask_identity_for_client}
                            onCheckedChange={(checked) => setData('mask_identity_for_client', checked === true)}
                        />
                        <Label>Masking field identitas untuk client</Label>
                    </div>
                    <div className="grid gap-2">
                        <Label>Produk</Label>
                        {clientProducts.map((product) => (
                            <label key={product.id} className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={data.product_ids.includes(product.id.toString())}
                                    onChange={(e) => {
                                        const value = product.id.toString();
                                        setData(
                                            'product_ids',
                                            e.target.checked ? [...data.product_ids, value] : data.product_ids.filter((id) => id !== value),
                                        );
                                    }}
                                />
                                {product.name}
                            </label>
                        ))}
                        <InputError message={errors.product_ids} />
                    </div>
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <Label>Lokasi</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setData('locations', [...data.locations, { ...EMPTY_LOCATION }])}
                            >
                                Tambah lokasi
                            </Button>
                        </div>
                        {data.locations.map((location, index) => {
                            const update = (patch: Partial<FormLocation>) => {
                                const next = [...data.locations];
                                next[index] = { ...next[index], ...patch };
                                setData('locations', next);
                            };
                            const fieldError = (key: string) => (errors as Record<string, string>)[`locations.${index}.${key}`];

                            return (
                                <div key={location.id ?? `new-${index}`} className="space-y-3 rounded-md border p-3">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium">Lokasi {index + 1}</span>
                                        {data.locations.length > 1 && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive"
                                                onClick={() =>
                                                    setData(
                                                        'locations',
                                                        data.locations.filter((_, i) => i !== index),
                                                    )
                                                }
                                            >
                                                Hapus lokasi
                                            </Button>
                                        )}
                                    </div>
                                    <div className="grid gap-2 md:grid-cols-2">
                                        <div className="grid gap-1">
                                            <Input
                                                placeholder="Nama lokasi, mis. PIK Avenue"
                                                value={location.name}
                                                onChange={(e) => update({ name: e.target.value })}
                                            />
                                            <InputError message={fieldError('name')} />
                                        </div>
                                        <Input placeholder="Area / kota" value={location.area} onChange={(e) => update({ area: e.target.value })} />
                                    </div>
                                    <Input placeholder="Alamat" value={location.address} onChange={(e) => update({ address: e.target.value })} />
                                    <Suspense fallback={<div className="bg-muted h-64 animate-pulse rounded-md" />}>
                                        <LocationPicker
                                            latitude={location.latitude}
                                            longitude={location.longitude}
                                            onPick={(picked) =>
                                                update({
                                                    latitude: picked.latitude,
                                                    longitude: picked.longitude,
                                                    ...(picked.address ? { address: picked.address } : {}),
                                                    ...(picked.area ? { area: picked.area } : {}),
                                                    // Nama hanya diisi otomatis bila EO belum mengetik nama sendiri.
                                                    ...(picked.name && !location.name ? { name: picked.name } : {}),
                                                })
                                            }
                                        />
                                    </Suspense>
                                    <InputError message={fieldError('latitude')} />
                                </div>
                            );
                        })}
                        <InputError message={errors.locations} />
                    </div>
                    <Button disabled={processing}>Simpan</Button>
                </form>
                {footer}
            </FormPage>
        </AppLayout>
    );
}
