import { Histogram, HourlyChart, QualifiedGauge, RankedBars, ShareDonut, TrendChart } from '@/components/dashboard-charts';
import PageHeader from '@/components/page-header';
import { SimpleSelect } from '@/components/simple-select';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { Head, router } from '@inertiajs/react';
import { toPng } from 'html-to-image';
import { jsPDF } from 'jspdf';
import { Ban, Download, Link2, ListChecks, X } from 'lucide-react';
import { useRef, useState } from 'react';

interface Summary {
    totals: { submissions: number; voided: number; qualified: number; campaigns: number };
    links: { open: number; total: number };
    per_day: { day: string; total: number; qualified: number }[];
    per_hour: { hour: number; total: number }[];
    per_location: { id: number; label: string; total: number }[];
    custom_fields: {
        key: string;
        label: string;
        type: string;
        responses: number;
        avg?: number | null;
        min?: number | null;
        max?: number | null;
        bins?: { label: string; total: number }[];
        options?: { label: string; total: number }[];
    }[];
}

interface Option {
    id: number;
    name: string;
}

type Filters = Partial<Record<'campaign_id' | 'location_id' | 'from' | 'to', string>>;

function isoDaysAgo(days: number): string {
    const date = new Date(Date.now() - days * 86400000);
    return date.toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });
}

const PRESETS = [
    { label: 'Today', from: () => isoDaysAgo(0) },
    { label: '7 days', from: () => isoDaysAgo(6) },
    { label: '30 days', from: () => isoDaysAgo(29) },
];

export default function Dashboard({
    summary,
    filters,
    campaigns,
    locations,
}: {
    summary: Summary;
    filters: Filters;
    campaigns: Option[];
    locations: (Option & { campaign_id: number })[];
}) {
    // Every filter change reloads the data right away; no "apply" step.
    const apply = (next: Filters) => {
        const merged = Object.fromEntries(Object.entries({ ...filters, ...next }).filter(([, value]) => value));
        router.get(route('dashboard'), merged, { preserveState: true, preserveScroll: true, replace: true });
    };

    const visibleLocations = filters.campaign_id ? locations.filter((l) => String(l.campaign_id) === filters.campaign_id) : locations;
    const { totals } = summary;
    const activeFilters = Object.values(filters).some(Boolean);
    const today = isoDaysAgo(0);

    const captureRef = useRef<HTMLDivElement>(null);
    const [downloading, setDownloading] = useState(false);

    // Saves the cards and charts (not the header or filters) as a one-page PDF sized to the content,
    // so charts are never split across pages. Keeps the current light/dark theme.
    const download = async () => {
        const node = captureRef.current;
        if (!node) return;
        setDownloading(true);
        try {
            // Padding is added to the captured copy only, so the cards don't touch the PDF edges.
            const padding = 40;
            const width = node.offsetWidth + padding * 2;
            const height = node.offsetHeight + padding * 2;
            const dataUrl = await toPng(node, {
                pixelRatio: 2,
                backgroundColor: getComputedStyle(document.body).backgroundColor,
                width,
                height,
                style: { padding: `${padding}px`, width: `${width}px`, height: `${height}px`, boxSizing: 'border-box' },
            });
            const pdf = new jsPDF({
                orientation: width > height ? 'landscape' : 'portrait',
                unit: 'px',
                format: [width, height],
                hotfixes: ['px_scaling'],
            });
            pdf.addImage(dataUrl, 'PNG', 0, 0, width, height);
            pdf.save(`dashboard-${today}.pdf`);
        } finally {
            setDownloading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <PageHeader title="Dashboard" description="Live results from the public form links. Invalid entries are not counted." />
                <Button type="button" variant="outline" onClick={download} disabled={downloading}>
                    <Download />
                    {downloading ? 'Preparing…' : 'Download PDF'}
                </Button>
            </div>

            {/* Filters: one row above the charts. */}
            <div className="flex flex-wrap items-end gap-3 rounded-lg border p-3">
                <FilterSelect
                    label="Campaign"
                    value={filters.campaign_id}
                    options={campaigns}
                    onChange={(value) => apply({ campaign_id: value, location_id: '' })}
                />
                <FilterSelect
                    label="Location"
                    value={filters.location_id}
                    options={visibleLocations}
                    onChange={(value) => apply({ location_id: value })}
                />
                <div className="grid gap-1">
                    <Label htmlFor="from" className="text-xs">
                        From
                    </Label>
                    <Input
                        id="from"
                        type="date"
                        className="h-9 w-40"
                        value={filters.from ?? ''}
                        max={filters.to || today}
                        onChange={(e) => apply({ from: e.target.value })}
                    />
                </div>
                <div className="grid gap-1">
                    <Label htmlFor="to" className="text-xs">
                        To
                    </Label>
                    <Input
                        id="to"
                        type="date"
                        className="h-9 w-40"
                        value={filters.to ?? ''}
                        min={filters.from}
                        onChange={(e) => apply({ to: e.target.value })}
                    />
                </div>
                <div className="flex flex-wrap gap-1">
                    {PRESETS.map((preset) => {
                        const from = preset.from();
                        const selected = filters.from === from && (!filters.to || filters.to === today);
                        return (
                            <Button key={preset.label} size="sm" variant={selected ? 'default' : 'outline'} onClick={() => apply({ from, to: '' })}>
                                {preset.label}
                            </Button>
                        );
                    })}
                    {activeFilters && (
                        <Button size="sm" variant="ghost" onClick={() => router.get(route('dashboard'), {}, { preserveScroll: true, replace: true })}>
                            <X className="size-4" />
                            Clear
                        </Button>
                    )}
                </div>
            </div>

            <div ref={captureRef} className="bg-background flex flex-col gap-4">
                {/* Headline numbers. */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <StatTile icon={ListChecks} title="Entries" value={totals.submissions.toLocaleString('en-GB')} />
                    <StatTile icon={Ban} title="Invalid" value={totals.voided.toLocaleString('en-GB')} hint="Not counted in charts" />
                    <StatTile
                        icon={Link2}
                        title="Open links"
                        value={`${summary.links.open} / ${summary.links.total}`}
                        hint="Locations accepting responses"
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Entries over time</CardTitle>
                            <CardDescription>Daily qualified and not qualified responses, or the running total</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <TrendChart rows={summary.per_day} />
                            {summary.per_day.length > 0 && (
                                <details className="mt-3 text-sm">
                                    <summary className="text-muted-foreground cursor-pointer">View as table</summary>
                                    <div className="mt-2 max-h-64 overflow-y-auto rounded-md border">
                                        <table className="w-full">
                                            <thead className="bg-muted/50 sticky top-0">
                                                <tr>
                                                    <th className="px-3 py-1.5 text-left font-medium">Day</th>
                                                    <th className="px-3 py-1.5 text-right font-medium">Qualified</th>
                                                    <th className="px-3 py-1.5 text-right font-medium">Not qualified</th>
                                                    <th className="px-3 py-1.5 text-right font-medium">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {summary.per_day.map((row) => (
                                                    <tr key={row.day} className="border-t tabular-nums">
                                                        <td className="px-3 py-1.5">{formatDate(row.day)}</td>
                                                        <td className="px-3 py-1.5 text-right">{row.qualified}</td>
                                                        <td className="px-3 py-1.5 text-right">{row.total - row.qualified}</td>
                                                        <td className="px-3 py-1.5 text-right font-medium">{row.total}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Qualification rate</CardTitle>
                            <CardDescription>Entries that answered Yes to every qualifying question</CardDescription>
                        </CardHeader>
                        <CardContent className="flex items-center justify-center">
                            <QualifiedGauge qualified={totals.qualified} total={totals.submissions} />
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Busiest hours</CardTitle>
                            <CardDescription>Entries by hour of day</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <HourlyChart rows={summary.per_hour} />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>By location</CardTitle>
                            <CardDescription>
                                {filters.location_id ? 'Filtered to one location. ' : ''}Click a slice or row to filter by that location.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ShareDonut
                                rows={summary.per_location}
                                selectedId={filters.location_id ? Number(filters.location_id) : null}
                                onSelect={(id) => apply({ location_id: String(id) === filters.location_id ? '' : String(id) })}
                            />
                        </CardContent>
                    </Card>
                </div>

                {summary.custom_fields.length > 0 && (
                    <>
                        <h2 className="text-lg font-semibold">Answers</h2>
                        <div className="grid gap-4 md:grid-cols-2">
                            {summary.custom_fields.map((field) => (
                                <Card key={field.key} className={cn(field.type === 'number' && 'md:col-span-2')}>
                                    <CardHeader>
                                        <CardTitle className="text-base">{field.label}</CardTitle>
                                        <CardDescription>{field.responses.toLocaleString('en-GB')} responses</CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {field.type === 'number' ? (
                                            <>
                                                <div className="grid grid-cols-3 gap-3 text-sm">
                                                    <MiniStat label="Average" value={field.avg} />
                                                    <MiniStat label="Lowest" value={field.min} />
                                                    <MiniStat label="Highest" value={field.max} />
                                                </div>
                                                <Histogram bins={field.bins ?? []} />
                                            </>
                                        ) : field.type === 'single_choice' ? (
                                            <ShareDonut rows={field.options ?? []} />
                                        ) : (
                                            <RankedBars rows={field.options ?? []} name="Responses" />
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}

function FilterSelect({ label, value, options, onChange }: { label: string; value?: string; options: Option[]; onChange: (value: string) => void }) {
    const id = `filter-${label.toLowerCase()}`;
    return (
        <div className="grid gap-1">
            <Label htmlFor={id} className="text-xs">
                {label}
            </Label>
            <SimpleSelect
                id={id}
                className="min-w-44"
                value={value ?? ''}
                onChange={onChange}
                emptyLabel="All"
                options={options.map((option) => ({ value: String(option.id), label: option.name }))}
            />
        </div>
    );
}

function StatTile({ icon: Icon, title, value, hint }: { icon: typeof ListChecks; title: string; value: string; hint?: string }) {
    return (
        <Card className="gap-0">
            <CardContent className="flex items-start justify-between gap-3 p-5">
                <div>
                    <div className="text-muted-foreground text-sm">{title}</div>
                    <div className="mt-1 text-3xl font-semibold tabular-nums">{value}</div>
                    {hint && <div className="text-muted-foreground mt-1 text-xs">{hint}</div>}
                </div>
                <Icon className="text-muted-foreground size-5" />
            </CardContent>
        </Card>
    );
}

function MiniStat({ label, value }: { label: string; value?: number | null }) {
    return (
        <div className="rounded-md border p-3">
            <div className="text-muted-foreground">{label}</div>
            <div className="text-2xl font-semibold tabular-nums">{value ?? '-'}</div>
        </div>
    );
}
