import FlashMessage from '@/components/flash-message';
import { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';

export default function SpgLayout({ children }: { children: React.ReactNode }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <div className="bg-background flex min-h-svh flex-col">
            <header className="border-b">
                <div className="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-3 md:px-6">
                    <div className="font-semibold">EO Campaign</div>
                    <div className="flex items-center gap-3 text-sm">
                        <span className="text-muted-foreground">{auth.user?.name}</span>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="hover:bg-muted flex items-center gap-1.5 rounded-md px-2 py-1.5"
                        >
                            <LogOut className="size-4" />
                            Keluar
                        </Link>
                    </div>
                </div>
            </header>
            <main className="mx-auto w-full max-w-7xl flex-1 space-y-4 p-4 md:p-6">
                <FlashMessage />
                {children}
            </main>
        </div>
    );
}
