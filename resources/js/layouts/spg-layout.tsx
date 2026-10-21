import FlashMessage from '@/components/flash-message';
import { Link, usePage } from '@inertiajs/react';
import { SharedData } from '@/types';
import { ClipboardList, LogOut, UserRound } from 'lucide-react';

export default function SpgLayout({ children, title }: { children: React.ReactNode; title: string }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <div className="bg-background mx-auto flex min-h-svh max-w-md flex-col">
            <header className="border-b px-4 py-3">
                <div className="text-xs text-muted-foreground">EO Campaign</div>
                <div className="flex items-center justify-between">
                    <h1 className="text-lg font-semibold">{title}</h1>
                    <div className="text-sm text-muted-foreground">{auth.user?.name}</div>
                </div>
            </header>
            <main className="flex-1 space-y-4 p-4 pb-24">
                <FlashMessage />
                {children}
            </main>
            <nav className="fixed inset-x-0 bottom-0 mx-auto flex max-w-md border-t bg-background">
                <Link href={route('spg.tasks.index')} className="flex flex-1 flex-col items-center gap-1 py-3 text-xs">
                    <ClipboardList className="size-5" />
                    Tugas
                </Link>
                <Link href={route('spg.submissions.index')} className="flex flex-1 flex-col items-center gap-1 py-3 text-xs">
                    <UserRound className="size-5" />
                    Data saya
                </Link>
                <Link href={route('logout')} method="post" as="button" className="flex flex-1 flex-col items-center gap-1 py-3 text-xs">
                    <LogOut className="size-5" />
                    Keluar
                </Link>
            </nav>
        </div>
    );
}
