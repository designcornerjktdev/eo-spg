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
    assignments: { scheduled: number; active: number; done: number; cancelled: number };
    per_day: { day: string; total: number }[];
    per_location: { label: string; total: number }[];
    per_spg: { label: string; total: number }[];
    custom_fields: { key: string; type: string; sum?: number; avg?: number; options?: { label: string; total: number }[] }[];
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
    spgs,
}: {
    summary: Summary;
    filters: Record<string, string>;
    campaigns: Option[];
    locations: Option[];
    spgs: Option[];
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
                {spgs.length > 0 && (
                    <div>
                        <Label>SPG</Label>
                        <select name="spg_id" defaultValue={filters.spg_id ?? ''} className="border-input h-9 w-full rounded-md border px-2 text-sm">
                            <option value="">All</option>
                            {spgs.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.name}
                                </option>
                            ))}
                        </select>
                    </div>
                )}
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
                <Metric title="Submission" value={summary.totals.submissions} />
                <Metric title="Qualified" value={summary.totals.qualified} />
                <Metric title="Voided" value={summary.totals.voided} />
                <Metric title="Campaign" value={summary.totals.campaigns} />
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <BarCard title="Per day" rows={summary.per_day.map((row) => ({ label: row.day, total: row.total }))} />
                <BarCard title="Per location" rows={summary.per_location} />
                {summary.per_spg.length > 0 && <BarCard title="Per SPG" rows={summary.per_spg} />}
                <Card>
                    <CardHeader>
                        <CardTitle>Assignment status</CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-2 text-sm">
                        <div>Scheduled: {summary.assignments.scheduled}</div>
                        <div>Active: {summary.assignments.active}</div>
                        <div>Done: {summary.assignments.done}</div>
                        <div>Cancelled: {summary.assignments.cancelled}</div>
                    </CardContent>
                </Card>
            </div>

            {summary.custom_fields.length > 0 && (
                <div className="grid gap-4 md:grid-cols-2">
                    {summary.custom_fields.map((field) => (
                        <Card key={field.key}>
                            <CardHeader>
                                <CardTitle>{field.key}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {field.type === 'number' ? (
                                    <div className="text-sm">
                                        Count {field.sum} · Average {field.avg}
                                    </div>
                                ) : (
                                    <BarList rows={field.options ?? []} />
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}

function Metric({ title, value }: { title: string; value: number }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-muted-foreground text-sm font-medium">{title}</CardTitle>
            </CardHeader>
            <CardContent className="text-3xl font-semibold">{value}</CardContent>
        </Card>
    );
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

function BarList({ rows }: { rows: { label: string; total: number }[] }) {
    const max = Math.max(...rows.map((row) => row.total), 1);

    if (rows.length === 0) {
        return <p className="text-muted-foreground text-sm">No entries yet.</p>;
    }

    return (
        <div className="space-y-2">
            {rows.map((row) => (
                <div key={row.label}>
                    <div className="mb-1 flex justify-between text-sm">
                        <span>{row.label}</span>
                        <span>{row.total}</span>
                    </div>
                    <div className="bg-muted h-2 rounded">
                        <div className="bg-primary h-2 rounded" style={{ width: `${(row.total / max) * 100}%` }} />
                    </div>
                </div>
            ))}
        </div>
    );
}
