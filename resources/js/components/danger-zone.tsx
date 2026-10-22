import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Bagian "hapus" di halaman edit. Jika server mengirim `blocker`, tombol hapus diganti penjelasan
 * kenapa data tidak boleh dihapus (biasanya karena sudah punya riwayat).
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
                        <DialogDescription>{confirmDescription ?? 'Tindakan ini tidak bisa dibatalkan.'}</DialogDescription>
                    </DialogHeader>
                    <div className="flex justify-end gap-2">
                        <Button variant="outline" onClick={() => setOpen(false)}>
                            Kembali
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
