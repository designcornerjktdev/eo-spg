import InputError from '@/components/input-error';
import Link from '@/components/link';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { SimpleSelect } from '@/components/simple-select';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, label } from '@/lib/format';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface SubmissionRow {
    id: number;
    submitted_at: string;
    status: string;
    qualified: boolean | null;
    campaign: { name: string };
    location: { name: string } | null;
}

interface LocationOption {
    id: number;
    name: string;
    campaign_id: number;
}

export default function SubmissionsIndex({
    submissions,
    filters,
    campaigns,
    locations,
    canImport = false,
}: {
    submissions: { data: SubmissionRow[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Record<string, string>;
    campaigns: { id: number; name: string }[];
    locations: LocationOption[];
    canImport?: boolean;
}) {
    const apply = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(route('submissions.index'), Object.fromEntries(new FormData(event.currentTarget)), { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Entries', href: '/submissions' }]}>
            <Head title="Entries" />
            <PageHeader title="Campaign entries" description="Filters also apply to the Excel export. Invalid entries are not exported." />
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
                        <a
                            href={`${route('submissions.export')}?${new URLSearchParams(Object.entries(filters).filter(([, value]) => value)).toString()}`}
                        >
                            Export Excel
                        </a>
                    </Button>
                    {canImport && <ImportDialog campaigns={campaigns} locations={locations} />}
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

function ImportDialog({ campaigns, locations }: { campaigns: { id: number; name: string }[]; locations: LocationOption[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<{ location_id: string; file: File | null }>({
        location_id: '',
        file: null,
    });
    const campaignName = (id: number) => campaigns.find((campaign) => campaign.id === id)?.name;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('submissions.import'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                if (!next) clearErrors();
            }}
        >
            <DialogTrigger asChild>
                <Button type="button" variant="outline">
                    Import Excel
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-[calc(100%-1rem)] md:max-w-lg">
                <DialogHeader className="text-left">
                    <DialogTitle>Import entries</DialogTitle>
                    <DialogDescription>
                        Upload an .xlsx or .csv file. Columns are matched to the form fields by label. Photos cannot be imported. If any row is
                        invalid, nothing is saved.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label>Location</Label>
                        <SimpleSelect
                            value={data.location_id}
                            onChange={(value) => setData('location_id', value)}
                            placeholder="Select a location"
                            options={locations.map((location) => ({
                                value: String(location.id),
                                label: `${campaignName(location.campaign_id) ?? ''} · ${location.name}`,
                            }))}
                        />
                        <InputError message={errors.location_id} />
                        {data.location_id && (
                            <a
                                className="text-sm underline underline-offset-4"
                                href={route('submissions.import-template', { location_id: data.location_id })}
                            >
                                Download the template for this form
                            </a>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="import-file">File</Label>
                        <Input id="import-file" type="file" accept=".xlsx,.xls,.csv" onChange={(e) => setData('file', e.target.files?.[0] ?? null)} />
                        {/* Row errors arrive as one message, one row per line. */}
                        {errors.file && (
                            <ul className="max-h-48 list-disc space-y-1 overflow-y-auto pl-5 text-sm text-red-600 dark:text-red-400">
                                {errors.file.split('\n').map((message) => (
                                    <li key={message}>{message}</li>
                                ))}
                            </ul>
                        )}
                    </div>
                    <DialogFooter>
                        <Button type="submit" disabled={processing || !data.location_id || !data.file}>
                            {processing ? 'Importing…' : 'Import'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function FilterSelect({ name, label, value, options }: { name: string; label: string; value?: string; options: { id: number; name: string }[] }) {
    return (
        <div>
            <Label>{label}</Label>
            <SimpleSelect
                name={name}
                defaultValue={value ?? ''}
                emptyLabel="All"
                options={options.map((option) => ({ value: String(option.id), label: option.name }))}
            />
        </div>
    );
}
