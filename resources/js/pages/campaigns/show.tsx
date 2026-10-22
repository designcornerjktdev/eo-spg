import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';

interface CampaignShow {
    id: number;
    name: string;
    status: string;
    starts_on: string;
    ends_on: string;
    mask_identity_for_client: boolean;
    client: { name: string };
    products: { id: number; name: string }[];
    locations: { id: number; name: string; area: string | null }[];
    form_templates: { id: number; name: string; version: number; status: string }[];
    assignments: { id: number; user: { name: string }; location: { name: string }; starts_at: string; ends_at: string; status: string }[];
}

export default function CampaignShow({ campaign }: { campaign: CampaignShow }) {
    const canEdit = usePage<SharedData>().props.auth.user?.role === 'superadmin';

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Campaign', href: '/campaigns' },
                { title: campaign.name, href: route('campaigns.show', campaign.id) },
            ]}
        >
            <Head title={campaign.name} />
            <div className="flex flex-wrap items-start justify-between gap-3">
                <PageHeader title={campaign.name} description={`${campaign.client.name} · ${campaign.status}`} />
                {canEdit && (
                    <div className="flex gap-2">
                        {campaign.status === 'draft' && (
                            <Button onClick={() => router.post(route('campaigns.activate', campaign.id))}>Aktifkan</Button>
                        )}
                        {campaign.status === 'active' && (
                            <Button variant="outline" onClick={() => router.post(route('campaigns.close', campaign.id))}>
                                Tutup
                            </Button>
                        )}
                        <Button asChild variant="outline">
                            <Link href={route('campaigns.edit', campaign.id)}>Ubah</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`${route('forms.create')}?campaign_id=${campaign.id}`}>Buat form</Link>
                        </Button>
                    </div>
                )}
            </div>
            <div className="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Produk & lokasi</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div>
                            Periode: {campaign.starts_on} — {campaign.ends_on}
                        </div>
                        <div>Masking identitas client: {campaign.mask_identity_for_client ? 'Ya' : 'Tidak'}</div>
                        <div>Produk: {campaign.products.map((product) => product.name).join(', ') || '-'}</div>
                        <div>
                            Lokasi:{' '}
                            {campaign.locations.map((location) => `${location.name}${location.area ? ` (${location.area})` : ''}`).join(', ') || '-'}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Form</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {campaign.form_templates.map((form) => (
                            <div key={form.id} className="flex justify-between gap-2">
                                <span>
                                    {form.name} v{form.version} · {form.status}
                                </span>
                                {canEdit && (
                                    <Link href={route('forms.edit', form.id)} className="underline">
                                        Kelola
                                    </Link>
                                )}
                            </div>
                        ))}
                        {campaign.form_templates.length === 0 && <div>Belum ada form.</div>}
                    </CardContent>
                </Card>
                <Card className="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Assignment</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {campaign.assignments.map((assignment) => (
                            <div key={assignment.id}>
                                {assignment.user.name} · {assignment.location.name} · {assignment.starts_at} — {assignment.ends_at} ·{' '}
                                {assignment.status}
                            </div>
                        ))}
                        {canEdit && (
                            <Button asChild variant="outline" size="sm">
                                <Link href={`${route('assignments.create')}?campaign_id=${campaign.id}`}>Tambah assignment</Link>
                            </Button>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
