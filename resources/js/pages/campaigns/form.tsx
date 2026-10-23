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
    radius_meters?: number;
}

interface Campaign {
    id: number;
    name: string;
    starts_on: string;
    ends_on: string;
    notes: string | null;
    mask_identity_for_client: boolean;
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
    radius_meters: number;
};

const DEFAULT_RADIUS = 300;
const EMPTY_LOCATION: FormLocation = { name: '', area: '', address: '', latitude: null, longitude: null, radius_meters: DEFAULT_RADIUS };

export default function CampaignForm({ campaign, footer }: { footer?: React.ReactNode; campaign?: Campaign }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: campaign?.name ?? '',
        starts_on: campaign?.starts_on?.slice(0, 10) ?? '',
        ends_on: campaign?.ends_on?.slice(0, 10) ?? '',
        notes: campaign?.notes ?? '',
        mask_identity_for_client: campaign?.mask_identity_for_client ?? true,
        locations: campaign?.locations?.length
            ? campaign.locations.map(
                  (location): FormLocation => ({
                      id: location.id,
                      name: location.name,
                      area: location.area ?? '',
                      address: location.address ?? '',
                      latitude: location.latitude ?? null,
                      longitude: location.longitude ?? null,
                      radius_meters: location.radius_meters ?? DEFAULT_RADIUS,
                  }),
              )
            : [{ ...EMPTY_LOCATION }],
    });

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
                { title: campaign ? 'Edit' : 'New', href: '#' },
            ]}
        >
            <Head title={campaign ? 'Edit campaign' : 'New campaign'} />
            <FormPage width="lg">
                <PageHeader title={campaign ? 'Edit campaign' : 'New campaign'} />
                <form onSubmit={submit} className="grid gap-4 rounded-xl border p-4 sm:p-6">
                    <div className="grid gap-2">
                        <Label>Name</Label>
                        <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Start</Label>
                            <Input type="date" value={data.starts_on} onChange={(e) => setData('starts_on', e.target.value)} />
                            <InputError message={errors.starts_on} />
                        </div>
                        <div className="grid gap-2">
                            <Label>End</Label>
                            <Input type="date" value={data.ends_on} onChange={(e) => setData('ends_on', e.target.value)} />
                            <InputError message={errors.ends_on} />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            checked={data.mask_identity_for_client}
                            onCheckedChange={(checked) => setData('mask_identity_for_client', checked === true)}
                        />
                        <Label>Mask identity fields for the client</Label>
                    </div>
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <Label>Location</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setData('locations', [...data.locations, { ...EMPTY_LOCATION }])}
                            >
                                Add location
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
                                        <span className="text-sm font-medium">Location {index + 1}</span>
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
                                                Remove location
                                            </Button>
                                        )}
                                    </div>
                                    <div className="grid gap-2 md:grid-cols-2">
                                        <div className="grid gap-1">
                                            <Input
                                                placeholder="Location name, e.g. PIK Avenue"
                                                value={location.name}
                                                onChange={(e) => update({ name: e.target.value })}
                                            />
                                            <InputError message={fieldError('name')} />
                                        </div>
                                        <Input placeholder="Area / city" value={location.area} onChange={(e) => update({ area: e.target.value })} />
                                    </div>
                                    <Input placeholder="Address" value={location.address} onChange={(e) => update({ address: e.target.value })} />
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
                                                    // Only auto-fill the name when the EO hasn't typed one yet.
                                                    ...(picked.name && !location.name ? { name: picked.name } : {}),
                                                })
                                            }
                                        />
                                    </Suspense>
                                    <InputError message={fieldError('latitude')} />
                                    <div className="grid gap-1 sm:max-w-xs">
                                        <Label htmlFor={`radius-${index}`}>Allowed radius (meters)</Label>
                                        <Input
                                            id={`radius-${index}`}
                                            type="number"
                                            min={50}
                                            max={5000}
                                            step={50}
                                            value={location.radius_meters}
                                            onChange={(e) => update({ radius_meters: Number(e.target.value) })}
                                        />
                                        <InputError message={fieldError('radius_meters')} />
                                    </div>
                                    <p
                                        className={
                                            location.latitude === null
                                                ? 'text-sm text-amber-700 dark:text-amber-300'
                                                : 'text-muted-foreground text-xs'
                                        }
                                    >
                                        {location.latitude === null
                                            ? 'No map point yet: entries for this location are accepted from anywhere. Pick a point to only accept entries on site.'
                                            : `Consumers can only submit within ${location.radius_meters} m of the pin (plus up to 100 m of GPS accuracy).`}
                                    </p>
                                </div>
                            );
                        })}
                        <InputError message={errors.locations} />
                    </div>
                    <Button disabled={processing}>Save</Button>
                </form>
                {footer}
            </FormPage>
        </AppLayout>
    );
}
