import Link from '@/components/link';
import PageHeader from '@/components/page-header';
import PublicLinks, { type LinkLocation } from '@/components/public-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDateRange, label } from '@/lib/format';
import { SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';

interface CampaignShow {
    id: number;
    name: string;
    status: string;
    starts_on: string;
    ends_on: string;
    mask_identity_for_client: boolean;
    viewers: { id: number; name: string; username: string | null }[];
    locations: (LinkLocation & { address: string | null; latitude: number | null; longitude: number | null })[];
    form_templates: { id: number; name: string; version: number; status: string }[];
}

export default function CampaignShow({ campaign, accepting }: { campaign: CampaignShow; accepting: boolean }) {
    const canEdit = usePage<SharedData>().props.auth.user?.role !== 'client';

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
                    <PageHeader title={campaign.name} description={formatDateRange(campaign.starts_on, campaign.ends_on)} />
                    <Badge variant={campaign.status === 'active' ? 'default' : 'secondary'}>{label('campaignStatus', campaign.status)}</Badge>
                </div>
                {canEdit && (
                    <div className="flex flex-wrap gap-2">
                        {campaign.status === 'draft' && (
                            <Button onClick={() => router.post(route('campaigns.activate', campaign.id))}>Activate</Button>
                        )}
                        {campaign.status === 'active' && (
                            <Button variant="outline" onClick={() => router.post(route('campaigns.close', campaign.id))}>
                                Close
                            </Button>
                        )}
                        <Button asChild variant="outline">
                            <Link href={route('campaigns.edit', campaign.id)}>Edit</Link>
                        </Button>
                        <Button asChild>
                            <Link href={`${route('forms.create')}?campaign_id=${campaign.id}`}>Create form</Link>
                        </Button>
                    </div>
                )}
            </div>
            <div className="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                            <dt className="text-muted-foreground">Period</dt>
                            <dd>{formatDateRange(campaign.starts_on, campaign.ends_on)}</dd>
                            <dt className="text-muted-foreground">Client access</dt>
                            <dd>
                                {campaign.viewers.length === 0 ? 'No client accounts yet' : campaign.viewers.map((viewer) => viewer.name).join(', ')}
                                {canEdit && (
                                    <Link href={route('users.index')} className="ml-2 underline">
                                        Manage
                                    </Link>
                                )}
                            </dd>
                            <dt className="text-muted-foreground">Location</dt>
                            <dd className="space-y-1">
                                {campaign.locations.length === 0 && '-'}
                                {campaign.locations.map((location) => (
                                    <div key={location.id}>
                                        {location.name}
                                        {location.area ? ` (${location.area})` : ''}
                                        {location.latitude !== null && location.longitude !== null && (
                                            <a
                                                href={`https://www.openstreetmap.org/?mlat=${location.latitude}&mlon=${location.longitude}#map=17/${location.latitude}/${location.longitude}`}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="ml-2 underline"
                                            >
                                                View map
                                            </a>
                                        )}
                                    </div>
                                ))}
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
                                            Manage
                                        </Link>
                                    )}
                                </span>
                            </div>
                        ))}
                        {campaign.form_templates.length === 0 && <div className="text-muted-foreground">No forms yet.</div>}
                    </CardContent>
                </Card>
                <Card className="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Public form links</CardTitle>
                        <p className="text-muted-foreground text-sm">Share a location's link or QR code with consumers. No login is needed.</p>
                    </CardHeader>
                    <CardContent>
                        <PublicLinks locations={campaign.locations} accepting={accepting} campaignName={campaign.name} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
