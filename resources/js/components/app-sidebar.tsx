import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ClipboardList, FileSpreadsheet, LayoutGrid, MapPin, Package, ScrollText, Users, Building2, ListChecks } from 'lucide-react';
import AppLogo from './app-logo';

const eoNav: NavItem[] = [
    { title: 'Dashboard', url: '/dashboard', icon: LayoutGrid },
    { title: 'Client', url: '/clients', icon: Building2 },
    { title: 'Produk', url: '/products', icon: Package },
    { title: 'Campaign', url: '/campaigns', icon: MapPin },
    { title: 'Form', url: '/forms', icon: FileSpreadsheet },
    { title: 'Assignment', url: '/assignments', icon: ClipboardList },
    { title: 'Data', url: '/submissions', icon: ListChecks },
    { title: 'Pengguna', url: '/users', icon: Users },
    { title: 'Audit log', url: '/audit', icon: ScrollText },
];

const clientNav: NavItem[] = [
    { title: 'Dashboard', url: '/dashboard', icon: LayoutGrid },
    { title: 'Campaign', url: '/campaigns', icon: MapPin },
    { title: 'Data', url: '/submissions', icon: ListChecks },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const items = auth.user?.role === 'client' ? clientNav : eoNav;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={auth.user?.home ?? '/dashboard'} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
