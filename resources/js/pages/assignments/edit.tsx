import DangerZone from '@/components/danger-zone';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import AssignmentForm from './form';

type Props = Parameters<typeof AssignmentForm>[0];

export default function AssignmentEdit({
    deleteBlocker,
    ...props
}: Omit<Props, 'assignment'> & {
    assignment: NonNullable<Props['assignment']> & { status: string };
    deleteBlocker: string | null;
}) {
    const { assignment } = props;
    const cancelled = assignment.status === 'cancelled';

    return (
        <AssignmentForm
            {...props}
            footer={
                <DangerZone
                    title="Hapus penugasan"
                    description="Penugasan belum punya data dari SPG."
                    confirmTitle="Hapus penugasan ini?"
                    deleteUrl={route('assignments.destroy', assignment.id)}
                    blocker={deleteBlocker}
                    errorKey="assignment"
                >
                    {deleteBlocker && !cancelled && (
                        <Button
                            variant="outline"
                            className="text-destructive"
                            onClick={() => {
                                if (confirm('Batalkan penugasan ini? SPG tidak bisa input data lagi dan jadwalnya jadi bebas.')) {
                                    router.post(route('assignments.cancel', assignment.id));
                                }
                            }}
                        >
                            Batalkan penugasan
                        </Button>
                    )}
                    {cancelled && <p className="text-muted-foreground text-sm">Penugasan ini sudah dibatalkan.</p>}
                </DangerZone>
            }
        />
    );
}
