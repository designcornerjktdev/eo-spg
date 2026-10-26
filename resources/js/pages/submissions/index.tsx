import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, label } from '@/lib/format';
import { Head, router } from '@inertiajs/react';
import Link from '@/components/link';
import { FormEvent } from 'react';

interface SubmissionRow {
    id: number;
    submitted_at: string;
    status: string;
    qualified: boolean | null;
    campaign: { name: string };
    location: { name: string } | null;
}

export default function SubmissionsIndex({
    submissions,
    filters,
    campaigns,
    locations,
}: {
    submissions: { data: SubmissionRow[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    campaigns: { id: number; name: string }[];
    locations: { id: number; name: string }[];
}) {
    const apply = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(route('submissions.index'), Object.fromEntries(new FormData(event.currentTarget)), { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Entries', href: '/submissions' }]}>
            <Head title="Entries" />
            <PageHeader title="Campaign entries" description="Filters also apply to the Excel export." />
            <form onSubmit={apply} className="grid gap-3 rounded-lg border p-4 md:grid-cols-6">
                <FilterSelect name="campaign_id" label="Campaign" value={filters.campaign_id} options={campaigns} />
                <FilterSelect name="location_id" label="Location" value={filters.location_id} options={locations} />
                <div>
                    <Label>From</Label>
                    <Input type="date" name="from" defaultValue={filters.from ?? ''} />
                </div>
                <div>
                    <Label>To</Label>
                    <Input type="date" name="to" defaultValue={filters.to ?? ''} />
                </div>
                <div>
                    <Label>Search</Label>
                    <Input name="q" defaultValue={filters.q ?? ''} />
                </div>
                <div className="flex items-end gap-2 md:col-span-6">
                    <Button type="submit">Filter</Button>
                    <Button asChild variant="outline">
                        <a href={`${route('submissions.export')}?${new URLSearchParams(Object.entries(filters).filter(([, value]) => value)).toString()}`}>Export Excel</a>
                    </Button>
                </div>
            </form>
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Time</th>
                            <th className="px-3 py-2 text-left">Campaign</th>
                            <th className="px-3 py-2 text-left">Location</th>
                            <th className="px-3 py-2 text-left">Status</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        {submissions.data.map((submission) => (
                            <tr key={submission.id} className="border-t">
                                <td className="px-3 py-2 whitespace-nowrap">{formatDateTime(submission.submitted_at)}</td>
                                <td className="px-3 py-2">{submission.campaign?.name}</td>
                                <td className="px-3 py-2">{submission.location?.name}</td>
                                <td className="px-3 py-2">{label('submissionStatus', submission.status)}</td>
                                <td className="px-3 py-2 text-right">
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={route('submissions.show', submission.id)}>Detail</Link>
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination links={submissions.links} />
        </AppLayout>
    );
}

function FilterSelect({ name, label, value, options }: { name: string; label: string; value?: string; options: { id: number; name: string }[] }) {
    return (
        <div>
            <Label>{label}</Label>
            <select name={name} defaultValue={value ?? ''} className="border-input h-9 w-full rounded-md border px-2 text-sm">
                <option value="">All</option>
                {options.map((option) => (
                    <option key={option.id} value={option.id}>
                        {option.name}
                    </option>
                ))}
            </select>
        </div>
    );
}
