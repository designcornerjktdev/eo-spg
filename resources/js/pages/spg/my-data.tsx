import Pagination from '@/components/pagination';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import SpgLayout from '@/layouts/spg-layout';
import { Head } from '@inertiajs/react';

interface Submission {
    id: number;
    submitted_at: string;
    status: string;
    campaign: { name: string };
    assignment: { location: { name: string } };
    answers: Record<string, string>;
}

export default function SpgMyData({
    submissions,
    todayCount,
}: {
    submissions: { data: Submission[]; links: { url: string | null; label: string; active: boolean }[] };
    todayCount: number;
}) {
    return (
        <SpgLayout title="Data saya">
            <Head title="Data saya" />
            <p className="text-muted-foreground text-sm">Hari ini: {todayCount} baris. Data tidak bisa diedit setelah dikirim.</p>
            <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                {submissions.data.map((submission) => (
                    <Card key={submission.id}>
                        <CardHeader>
                            <CardTitle className="text-base">{submission.campaign.name}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <div>{submission.submitted_at}</div>
                            <div>{submission.assignment.location.name}</div>
                            <div>Status: {submission.status}</div>
                        </CardContent>
                    </Card>
                ))}
            </div>
            <Pagination links={submissions.links} />
        </SpgLayout>
    );
}
