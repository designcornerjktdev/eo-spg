import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDateTimeRange } from '@/lib/format';
import { cn } from '@/lib/utils';
import { Head, Link } from '@inertiajs/react';

interface Task {
    id: number;
    campaign: string | null;
    location: string | null;
    area: string | null;
    starts_at: string;
    ends_at: string;
    fillable: boolean;
    my_count: number;
}

export default function SpgIndex({ assignments }: { assignments: Task[] }) {
    const active = assignments.filter((task) => task.fillable);
    const others = assignments.filter((task) => !task.fillable);

    return (
        <AppLayout breadcrumbs={[{ title: 'Tasks', href: route('spg.tasks.index') }]}>
            <Head title="Tasks" />
            <PageHeader title="Tasks" description="Pick a task to view and submit entries." />

            <section className="space-y-3">
                <h2 className="text-sm font-semibold">Active now</h2>
                {active.length === 0 ? (
                    <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">No active tasks right now.</p>
                ) : (
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {active.map((task) => (
                            <TaskCard key={task.id} task={task} />
                        ))}
                    </div>
                )}
            </section>

            {others.length > 0 && (
                <section className="space-y-3">
                    <h2 className="text-muted-foreground text-sm font-semibold">Other tasks</h2>
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {others.map((task) => (
                            <TaskCard key={task.id} task={task} />
                        ))}
                    </div>
                </section>
            )}
        </AppLayout>
    );
}

function TaskCard({ task }: { task: Task }) {
    return (
        <div className={cn('flex flex-col gap-3 rounded-lg border p-4', !task.fillable && 'bg-muted/40')}>
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <div className="truncate font-semibold">{task.campaign}</div>
                    <div className="text-muted-foreground text-sm">{[task.location, task.area].filter(Boolean).join(' · ')}</div>
                </div>
                {task.fillable ? <Badge>Active</Badge> : <Badge variant="secondary">Closed</Badge>}
            </div>
            <div className="text-muted-foreground text-sm">
                {formatDateTimeRange(task.starts_at, task.ends_at)}
                <br />
                {task.my_count} entries submitted
            </div>
            <Button asChild variant={task.fillable ? 'default' : 'outline'} className="mt-auto">
                <Link href={route('spg.tasks.show', task.id)}>{task.fillable ? 'Open & add entries' : 'View entries'}</Link>
            </Button>
        </div>
    );
}
