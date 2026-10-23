import PageHeader from '@/components/page-header';
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
                { title: `#${submission.id}`, href: '#' },
            ]}
        >
            <Head title={`Submission #${submission.id}`} />
            <PageHeader
                title={`Submission #${submission.id}`}
                description={`${submission.campaign?.name} · ${label('submissionStatus', submission.status)}`}
            />
            <div className="grid gap-2 text-sm">
                <div>Submitted at: {formatDateTime(submission.submitted_at)}</div>
                <div>
                    Location: {submission.location?.name} {submission.location?.area ? `(${submission.location.area})` : ''}
                </div>
                <div>Qualification: {submission.qualified === null ? '-' : submission.qualified ? 'Qualified' : 'Not qualified'}</div>
            </div>
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <tbody>
                        {submission.fields.map((field) => (
                            <tr key={field.key} className="border-t">
                                <th className="px-3 py-2 text-left font-medium">{field.label}</th>
                                <td className="px-3 py-2">{formatAnswer(submission.answers[field.key])}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {submission.can_void && (
                <form onSubmit={voidSubmit} className="grid max-w-lg gap-3 rounded-lg border p-4">
                    <p className="text-sm">Voiding requires a reason and is recorded in the audit log. Data is never overwritten.</p>
                    <Textarea value={data.void_reason} onChange={(e) => setData('void_reason', e.target.value)} placeholder="Void reason" />
                    <Button variant="destructive" disabled={processing}>
                        Void entry
                    </Button>
                </form>
            )}
            {submission.void_reason && <p className="text-muted-foreground text-sm">Void reason: {submission.void_reason}</p>}
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
