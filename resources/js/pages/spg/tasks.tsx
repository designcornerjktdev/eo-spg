import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import SpgLayout from '@/layouts/spg-layout';
import { Head, Link } from '@inertiajs/react';

interface Assignment {
    id: number;
    fillable: boolean;
    starts_at: string;
    ends_at: string;
    status: string;
    campaign: { name: string };
    location: { name: string; area: string | null };
}

export default function SpgTasks({ assignments }: { assignments: Assignment[] }) {
    return (
        <SpgLayout title="Tugas saya">
            <Head title="Tugas" />
            <div className="space-y-3">
                {assignments.map((assignment) => (
                    <Card key={assignment.id}>
                        <CardHeader>
                            <CardTitle className="text-base">{assignment.campaign.name}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div>
                                {assignment.location.name}
                                {assignment.location.area ? ` · ${assignment.location.area}` : ''}
                            </div>
                            <div>
                                {assignment.starts_at} — {assignment.ends_at}
                            </div>
                            <Button asChild className="w-full" disabled={!assignment.fillable}>
                                <Link href={route('spg.tasks.fill', assignment.id)}>Isi form</Link>
                            </Button>
                        </CardContent>
                    </Card>
                ))}
                {assignments.length === 0 && <p className="text-sm text-muted-foreground">Belum ada tugas.</p>}
            </div>
        </SpgLayout>
    );
}
