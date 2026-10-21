import FlashMessage from '@/components/flash-message';
import { cn } from '@/lib/utils';
import { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ClipboardList, LogOut, UserRound } from 'lucide-react';

export default function SpgLayout({ children, title }: { children: React.ReactNode; title: string }) {
    const { auth } = usePage<SharedData>().props;

    const navItems = [
        { href: route('spg.tasks.index'), label: 'Tugas', icon: ClipboardList, active: route().current('spg.tasks.*') },
        { href: route('spg.submissions.index'), label: 'Data saya', icon: UserRound, active: route().current('spg.submissions.*') },
    ];

    return (
        <div className="bg-background flex min-h-svh flex-col">
            <header className="border-b">
                <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-3 md:px-6">
                    <div className="min-w-0">
                        <div className="text-muted-foreground text-xs">EO Campaign</div>
                        <h1 className="truncate text-lg font-semibold">{title}</h1>
                    </div>
                    {/* Tablet & laptop: navigasi di header */}
                    <nav className="hidden items-center gap-1 md:flex">
                        {navItems.map((item) => (
                            <Link
                                key={item.label}
                                href={item.href}
                                className={cn(
                                    'hover:bg-muted flex items-center gap-2 rounded-md px-3 py-2 text-sm',
                                    item.active && 'bg-muted font-medium',
                                )}
                            >
                                <item.icon className="size-4" />
                                {item.label}
                            </Link>
                        ))}
                        <span className="text-muted-foreground mx-2 text-sm">{auth.user?.name}</span>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="hover:bg-muted flex items-center gap-2 rounded-md px-3 py-2 text-sm"
                        >
                            <LogOut className="size-4" />
                            Keluar
                        </Link>
                    </nav>
                    <div className="text-muted-foreground text-sm md:hidden">{auth.user?.name}</div>
                </div>
            </header>
            <main className="mx-auto w-full max-w-6xl flex-1 space-y-4 p-4 pb-24 md:p-6 md:pb-6">
                <FlashMessage />
                {children}
            </main>
            {/* Mobile: navigasi bawah */}
            <nav className="bg-background fixed inset-x-0 bottom-0 flex border-t md:hidden">
                {navItems.map((item) => (
                    <Link
                        key={item.label}
                        href={item.href}
                        className={cn(
                            'flex flex-1 flex-col items-center gap-1 py-3 text-xs',
                            item.active ? 'text-foreground font-medium' : 'text-muted-foreground',
                        )}
                    >
                        <item.icon className="size-5" />
                        {item.label}
                    </Link>
                ))}
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="text-muted-foreground flex flex-1 flex-col items-center gap-1 py-3 text-xs"
                >
                    <LogOut className="size-5" />
                    Keluar
                </Link>
            </nav>
        </div>
    );
}
