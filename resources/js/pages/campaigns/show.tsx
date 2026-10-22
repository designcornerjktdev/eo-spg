import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDateRange, formatDateTimeRange, label } from '@/lib/format';
import { cn } from '@/lib/utils';
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
                <div className="space-y-2">
                    <PageHeader
                        title={campaign.name}
                        description={`${campaign.client.name} · ${formatDateRange(campaign.starts_on, campaign.ends_on)}`}
                    />
                    <Badge variant={campaign.status === 'active' ? 'default' : 'secondary'}>{label('campaignStatus', campaign.status)}</Badge>
                </div>
                {canEdit && (
                    <div className="flex flex-wrap gap-2">
                        {campaign.status === 'draft' && (
                            <Button onClick={() => router.post(route('campaigns.activate', campaign.id))}>Aktifkan</Button>
                        )}
                        {campaign.status === 'active' && (
                            <Button variant="outline" onClick={() => router.post(route('campaigns.close', campaign.id))}>
                                Tutup
                            </Button>
                        )}
                        <Button asChild variant="outline">
                            <Link href={route('campaigns.edit', campaign.id)}>Edit</Link>
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
                    <CardContent>
                        <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                            <dt className="text-muted-foreground">Periode</dt>
                            <dd>{formatDateRange(campaign.starts_on, campaign.ends_on)}</dd>
                            <dt className="text-muted-foreground">Masking identitas</dt>
                            <dd>{campaign.mask_identity_for_client ? 'Ya, identitas konsumen disamarkan untuk client' : 'Tidak'}</dd>
                            <dt className="text-muted-foreground">Produk</dt>
                            <dd>{campaign.products.map((product) => product.name).join(', ') || '-'}</dd>
                            <dt className="text-muted-foreground">Lokasi</dt>
                            <dd>
                                {campaign.locations.map((location) => `${location.name}${location.area ? ` (${location.area})` : ''}`).join(', ') ||
                                    '-'}
                            </dd>
                        </dl>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Form</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {campaign.form_templates.map((form) => (
                            <div key={form.id} className="flex items-center justify-between gap-2">
                                <span>
                                    {form.name} <span className="text-muted-foreground">v{form.version}</span>
                                </span>
                                <span className="flex items-center gap-2">
                                    <Badge variant={form.status === 'draft' ? 'outline' : 'secondary'}>{label('formStatus', form.status)}</Badge>
                                    {canEdit && (
                                        <Link href={route('forms.edit', form.id)} className="underline">
                                            Kelola
                                        </Link>
                                    )}
                                </span>
                            </div>
                        ))}
                        {campaign.form_templates.length === 0 && <div className="text-muted-foreground">Belum ada form.</div>}
                    </CardContent>
                </Card>
                <Card className="md:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle>Penugasan SPG</CardTitle>
                        {canEdit && (
                            <Button asChild variant="outline" size="sm">
                                <Link href={`${route('assignments.create')}?campaign_id=${campaign.id}`}>Tambah penugasan</Link>
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        {campaign.assignments.length === 0 ? (
                            <p className="text-muted-foreground text-sm">Belum ada penugasan.</p>
                        ) : (
                            <div className="overflow-x-auto rounded-lg border">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50">
                                        <tr>
                                            <th className="px-3 py-2 text-left">SPG</th>
                                            <th className="px-3 py-2 text-left">Lokasi</th>
                                            <th className="px-3 py-2 text-left">Waktu</th>
                                            <th className="px-3 py-2 text-left">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {campaign.assignments.map((assignment) => (
                                            <tr
                                                key={assignment.id}
                                                className={cn('border-t', assignment.status === 'cancelled' && 'text-muted-foreground')}
                                            >
                                                <td className="px-3 py-2">{assignment.user.name}</td>
                                                <td className="px-3 py-2">{assignment.location.name}</td>
                                                <td className="px-3 py-2 whitespace-nowrap">
                                                    {formatDateTimeRange(assignment.starts_at, assignment.ends_at)}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <Badge variant={assignment.status === 'cancelled' ? 'outline' : 'secondary'}>
                                                        {label('assignmentStatus', assignment.status)}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
