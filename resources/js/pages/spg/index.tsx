import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
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
        <AppLayout breadcrumbs={[{ title: 'Tugas', href: route('spg.tasks.index') }]}>
            <Head title="Tugas" />
            <PageHeader title="Tugas" description="Pilih tugas untuk melihat dan menginput data." />

            <section className="space-y-3">
                <h2 className="text-sm font-semibold">Aktif sekarang</h2>
                {active.length === 0 ? (
                    <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                        Tidak ada tugas yang aktif saat ini.
                    </p>
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
                    <h2 className="text-muted-foreground text-sm font-semibold">Tugas lainnya</h2>
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
                {task.fillable ? <Badge>Aktif</Badge> : <Badge variant="secondary">Tutup</Badge>}
            </div>
            <div className="text-muted-foreground text-sm">
                {formatTime(task.starts_at)} – {formatTime(task.ends_at)}
                <br />
                {task.my_count} data terinput
            </div>
            <Button asChild variant={task.fillable ? 'default' : 'outline'} className="mt-auto">
                <Link href={route('spg.tasks.show', task.id)}>{task.fillable ? 'Buka & input data' : 'Lihat data'}</Link>
            </Button>
        </div>
    );
}

function formatTime(value: string) {
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}
