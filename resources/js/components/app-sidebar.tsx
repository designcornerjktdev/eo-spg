import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavGroup, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Building2, ClipboardList, FileSpreadsheet, LayoutGrid, ListChecks, MapPin, Package, ScrollText, Users } from 'lucide-react';
import AppLogo from './app-logo';

// Group order follows the EO workflow: set up the campaign -> run it in the field -> manage access.
const eoNav: NavGroup[] = [
    { title: 'Overview', items: [{ title: 'Dashboard', url: '/dashboard', icon: LayoutGrid }] },
    {
        title: 'Campaign setup',
        items: [
            { title: 'Client', url: '/clients', icon: Building2 },
            { title: 'Products', url: '/products', icon: Package },
            { title: 'Campaign', url: '/campaigns', icon: MapPin },
            { title: 'Form', url: '/forms', icon: FileSpreadsheet },
        ],
    },
    {
        title: 'Field operations',
        items: [
            { title: 'SPG assignments', url: '/assignments', icon: ClipboardList },
            { title: 'Entries', url: '/submissions', icon: ListChecks },
        ],
    },
    {
        title: 'Administration',
        items: [
            { title: 'Users', url: '/users', icon: Users },
            { title: 'Audit log', url: '/audit', icon: ScrollText },
        ],
    },
];

const clientNav: NavGroup[] = [
    {
        title: 'Menu',
        items: [
            { title: 'Dashboard', url: '/dashboard', icon: LayoutGrid },
            { title: 'Campaign', url: '/campaigns', icon: MapPin },
            { title: 'Entries', url: '/submissions', icon: ListChecks },
        ],
    },
];

const spgNav: NavGroup[] = [{ title: 'Menu', items: [{ title: 'Tasks', url: '/tasks', icon: ClipboardList }] }];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const role = auth.user?.role;
    const groups = role === 'client' ? clientNav : role === 'spg' ? spgNav : eoNav;

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
                {groups.map((group) => (
                    <NavMain key={group.title} label={group.title} items={group.items} />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
