import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Summary {
    totals: { submissions: number; voided: number; qualified: number; campaigns: number };
    links: { open: number; total: number };
    per_day: { day: string; total: number }[];
    per_location: { label: string; total: number }[];
    custom_fields: {
        key: string;
        label: string;
        type: string;
        responses: number;
        avg?: number | null;
        min?: number | null;
        max?: number | null;
        options?: { label: string; total: number }[];
    }[];
}

interface Option {
    id: number;
    name: string;
}

export default function Dashboard({
    summary,
    filters,
    campaigns,
    locations,
}: {
    summary: Summary;
    filters: Record<string, string>;
    campaigns: Option[];
    locations: Option[];
}) {
    const apply = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries());
        router.get(route('dashboard'), data, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}>
            <Head title="Dashboard" />
            <PageHeader title="Dashboard" description="Totals are calculated from the same entries shown in the entries list." />

            <form onSubmit={apply} className="grid gap-3 rounded-lg border p-4 md:grid-cols-5">
                <div>
                    <Label>Campaign</Label>
                    <select
                        name="campaign_id"
                        defaultValue={filters.campaign_id ?? ''}
                        className="border-input h-9 w-full rounded-md border px-2 text-sm"
                    >
                        <option value="">All</option>
                        {campaigns.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.name}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <Label>Location</Label>
                    <select
                        name="location_id"
                        defaultValue={filters.location_id ?? ''}
                        className="border-input h-9 w-full rounded-md border px-2 text-sm"
                    >
                        <option value="">All</option>
                        {locations.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.name}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <Label>From</Label>
                    <Input type="date" name="from" defaultValue={filters.from ?? ''} />
                </div>
                <div>
                    <Label>To</Label>
                    <Input type="date" name="to" defaultValue={filters.to ?? ''} />
                </div>
                <div className="md:col-span-5">
                    <Button type="submit">Apply filters</Button>
                </div>
            </form>

            <div className="grid gap-4 md:grid-cols-4">
                <Metric title="Entries" value={summary.totals.submissions} />
                <Metric title="Qualified" value={summary.totals.qualified} hint={percent(summary.totals.qualified, summary.totals.submissions)} />
                <Metric title="Voided" value={summary.totals.voided} />
                <Metric title="Campaigns" value={summary.totals.campaigns} />
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <BarCard title="Per day" rows={summary.per_day.map((row) => ({ label: formatDayLabel(row.day), total: row.total }))} />
                <BarCard title="Per location" rows={summary.per_location} />
                <Card>
                    <CardHeader>
                        <CardTitle>Public links</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-1">
                        <div className="text-3xl font-semibold">
                            {summary.links.open} <span className="text-muted-foreground text-base font-normal">of {summary.links.total} open</span>
                        </div>
                        <p className="text-muted-foreground text-sm">Locations whose link accepts responses right now.</p>
                    </CardContent>
                </Card>
            </div>

            {summary.custom_fields.length > 0 && (
                <div className="grid gap-4 md:grid-cols-2">
                    {summary.custom_fields.map((field) => (
                        <Card key={field.key}>
                            <CardHeader>
                                <CardTitle>{field.label}</CardTitle>
                                <p className="text-muted-foreground text-sm">{field.responses} responses</p>
                            </CardHeader>
                            <CardContent>
                                {field.type === 'number' ? (
                                    <dl className="grid grid-cols-3 gap-3 text-sm">
                                        {(
                                            [
                                                ['Average', field.avg],
                                                ['Lowest', field.min],
                                                ['Highest', field.max],
                                            ] as const
                                        ).map(([name, value]) => (
                                            <div key={name} className="rounded-md border p-3">
                                                <dt className="text-muted-foreground">{name}</dt>
                                                <dd className="text-2xl font-semibold">{value ?? '-'}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                ) : (
                                    <BarList rows={field.options ?? []} showShare />
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}

function Metric({ title, value, hint }: { title: string; value: number; hint?: string }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-muted-foreground text-sm font-medium">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <span className="text-3xl font-semibold">{value.toLocaleString('en-GB')}</span>
                {hint && <span className="text-muted-foreground ml-2 text-sm">{hint}</span>}
            </CardContent>
        </Card>
    );
}

function percent(part: number, whole: number): string | undefined {
    return whole > 0 ? `${Math.round((part / whole) * 100)}%` : undefined;
}

// "Thu, 9 Oct" — short enough for the chart rows, with the weekday to spot weekend peaks.
function formatDayLabel(day: string): string {
    const date = new Date(`${day}T00:00:00+07:00`);
    return Number.isNaN(date.getTime())
        ? day
        : date.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'Asia/Jakarta' });
}

function BarCard({ title, rows }: { title: string; rows: { label: string; total: number }[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <BarList rows={rows} />
            </CardContent>
        </Card>
    );
}

function BarList({ rows, showShare = false }: { rows: { label: string; total: number }[]; showShare?: boolean }) {
    const max = Math.max(...rows.map((row) => row.total), 1);
    const sum = rows.reduce((total, row) => total + row.total, 0);

    if (rows.length === 0) {
        return <p className="text-muted-foreground text-sm">No entries yet.</p>;
    }

    return (
        <div className="space-y-2">
            {rows.map((row) => (
                <div key={row.label}>
                    <div className="mb-1 flex justify-between text-sm">
                        <span>{row.label}</span>
                        <span>
                            {row.total}
                            {showShare && <span className="text-muted-foreground ml-1">({percent(row.total, sum)})</span>}
                        </span>
                    </div>
                    <div className="bg-muted h-2 rounded">
                        <div className="bg-primary h-2 rounded" style={{ width: `${(row.total / max) * 100}%` }} />
                    </div>
                </div>
            ))}
        </div>
    );
}
