import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
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
    spg: { name: string } | null;
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
        <AppLayout breadcrumbs={[{ title: 'Data', href: '/submissions' }, { title: `#${submission.id}`, href: '#' }]}>
            <Head title={`Submission #${submission.id}`} />
            <PageHeader title={`Submission #${submission.id}`} description={`${submission.campaign?.name} · ${submission.status}`} />
            <div className="grid gap-2 text-sm">
                <div>Waktu server: {submission.submitted_at}</div>
                <div>Lokasi: {submission.location?.name} {submission.location?.area ? `(${submission.location.area})` : ''}</div>
                <div>SPG: {submission.spg?.name}</div>
                <div>Kualifikasi: {submission.qualified === null ? '-' : submission.qualified ? 'Lolos' : 'Tidak lolos'}</div>
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
                    <p className="text-sm">Pembatalan wajib beralasan dan tercatat di audit log. Data tidak ditimpa.</p>
                    <Textarea value={data.void_reason} onChange={(e) => setData('void_reason', e.target.value)} placeholder="Alasan void" />
                    <Button variant="destructive" disabled={processing}>
                        Batalkan submission
                    </Button>
                </form>
            )}
            {submission.void_reason && <p className="text-sm text-muted-foreground">Alasan void: {submission.void_reason}</p>}
        </AppLayout>
    );
}

function formatAnswer(value: string | string[] | boolean | number | undefined): string {
    if (Array.isArray(value)) {
        return value.join(', ');
    }

    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }

    return value === undefined || value === null ? '-' : String(value);
}
