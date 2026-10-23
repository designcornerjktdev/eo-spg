import FormPage from '@/components/form-page';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, label } from '@/lib/format';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Field {
    key: string;
    label: string;
}

interface Submission {
    id: number;
    submitted_at: string;
    status: string;
    qualified: boolean | null;
    void_reason: string | null;
    can_void: boolean;
    campaign: { name: string } | null;
    location: { name: string; area: string | null } | null;
    distance_meters: number | null;
    location_accuracy_meters: number | null;
    answers: Record<string, string | string[] | boolean | number>;
    fields: Field[];
}

export default function SubmissionShow({ submission }: { submission: Submission }) {
    const { data, setData, post, processing } = useForm({ void_reason: '' });

    const voidSubmit = (event: FormEvent) => {
        event.preventDefault();
        post(route('submissions.void', submission.id));
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Entries', href: '/submissions' },
                { title: `Entry #${submission.id}`, href: '#' },
            ]}
        >
            <Head title={`Entry #${submission.id}`} />
            <FormPage>
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <PageHeader title={`Entry #${submission.id}`} description={submission.campaign?.name ?? ''} />
                    <Badge variant={submission.status === 'void' ? 'destructive' : 'secondary'}>{label('submissionStatus', submission.status)}</Badge>
                </div>

                {submission.void_reason && (
                    <p className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                        Void reason: {submission.void_reason}
                    </p>
                )}

                <dl className="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 rounded-xl border p-4 text-sm sm:p-6">
                    <dt className="text-muted-foreground">Submitted at</dt>
                    <dd>{formatDateTime(submission.submitted_at)}</dd>
                    <dt className="text-muted-foreground">Location</dt>
                    <dd>
                        {submission.location?.name ?? '-'}
                        {submission.location?.area ? ` (${submission.location.area})` : ''}
                    </dd>
                    <dt className="text-muted-foreground">Location check</dt>
                    <dd>
                        {submission.distance_meters === null
                            ? 'Not checked (location had no map point)'
                            : `${submission.distance_meters} m from the location pin (GPS accuracy ±${submission.location_accuracy_meters ?? '?'} m)`}
                    </dd>
                    <dt className="text-muted-foreground">Qualification</dt>
                    <dd>{submission.qualified === null ? '-' : submission.qualified ? 'Qualified' : 'Not qualified'}</dd>
                </dl>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <tbody>
                            {submission.fields.map((field, index) => (
                                <tr key={field.key} className={index > 0 ? 'border-t' : undefined}>
                                    <th className="bg-muted/40 w-2/5 px-4 py-2 text-left align-top font-medium">{field.label}</th>
                                    <td className="px-4 py-2 break-words">{formatAnswer(submission.answers[field.key])}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {submission.can_void && (
                    <form onSubmit={voidSubmit} className="grid gap-3 rounded-xl border border-red-200 p-4 sm:p-6 dark:border-red-900">
                        <h2 className="font-semibold">Void entry</h2>
                        <p className="text-muted-foreground text-sm">
                            Voiding requires a reason and is recorded in the audit log. Data is never overwritten.
                        </p>
                        <Textarea value={data.void_reason} onChange={(e) => setData('void_reason', e.target.value)} placeholder="Void reason" />
                        <Button variant="destructive" className="justify-self-start" disabled={processing}>
                            Void entry
                        </Button>
                    </form>
                )}
            </FormPage>
        </AppLayout>
    );
}

function formatAnswer(value: string | string[] | boolean | number | undefined): string {
    if (Array.isArray(value)) {
        return value.join(', ');
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    return value === undefined || value === null ? '-' : String(value);
}
