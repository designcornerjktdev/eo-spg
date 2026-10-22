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
                    title="Delete assignment"
                    description="This assignment has no SPG entries yet."
                    confirmTitle="Delete this assignment?"
                    deleteUrl={route('assignments.destroy', assignment.id)}
                    blocker={deleteBlocker}
                    errorKey="assignment"
                >
                    {deleteBlocker && !cancelled && (
                        <Button
                            variant="outline"
                            className="text-destructive"
                            onClick={() => {
                                if (confirm('Cancel this assignment? The SPG can no longer submit entries and their schedule is freed.')) {
                                    router.post(route('assignments.cancel', assignment.id));
                                }
                            }}
                        >
                            Cancel assignment
                        </Button>
                    )}
                    {cancelled && <p className="text-muted-foreground text-sm">This assignment has been cancelled.</p>}
                </DangerZone>
            }
        />
    );
}
