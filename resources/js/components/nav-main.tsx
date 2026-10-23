import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { usePage } from '@inertiajs/react';
import Link from '@/components/link';
import { stripBase } from '@/lib/base';

// Keep the menu item active on sub-pages, e.g. /campaigns/create highlights "Campaign".
function isActive(currentUrl: string, itemUrl: string) {
    const path = stripBase(currentUrl.split('?')[0]);
    return path === itemUrl || path.startsWith(itemUrl + '/');
}

export function NavMain({ items = [], label = 'Menu' }: { items: NavItem[]; label?: string }) {
    const page = usePage();
    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{label}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton asChild isActive={isActive(page.url, item.url)} tooltip={{ children: item.title }}>
                            <Link href={item.url} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
