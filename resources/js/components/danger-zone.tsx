import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

/**
 * "Delete" section on edit pages. When the server sends a `blocker`, the delete button is replaced
 * by an explanation of why the record cannot be deleted (usually because it already has history).
 */
export default function DangerZone({
    title,
    description,
    confirmTitle,
    confirmDescription,
    deleteUrl,
    blocker,
    errorKey,
    children,
}: {
    title: string;
    description: string;
    confirmTitle: string;
    confirmDescription?: string;
    deleteUrl: string;
    blocker: string | null;
    errorKey: string;
    children?: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const errors = usePage().props.errors as Record<string, string>;

    return (
        <div className="space-y-2 rounded-lg border border-red-200 p-4 dark:border-red-900">
            <h2 className="font-semibold">{title}</h2>
            {blocker ? (
                <p className="text-muted-foreground text-sm">{blocker}</p>
            ) : (
                <>
                    <p className="text-muted-foreground text-sm">{description}</p>
                    <Button variant="destructive" onClick={() => setOpen(true)}>
                        {title}
                    </Button>
                </>
            )}
            {children}
            <InputError message={errors[errorKey]} />

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-[calc(100%-1rem)] md:max-w-md">
                    <DialogHeader className="text-left">
                        <DialogTitle>{confirmTitle}</DialogTitle>
                        <DialogDescription>{confirmDescription ?? 'This action cannot be undone.'}</DialogDescription>
                    </DialogHeader>
                    <div className="flex justify-end gap-2">
                        <Button variant="outline" onClick={() => setOpen(false)}>
                            Back
                        </Button>
                        <Button variant="destructive" onClick={() => router.delete(deleteUrl, { onFinish: () => setOpen(false) })}>
                            {title}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    );
}
