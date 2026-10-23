import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface Branding {
    name: string;
    logo_url: string | null;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    branding: Branding;
    auth: Auth;
    flash?: { success?: string | null; submitted?: boolean };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string | null;
    username?: string | null;
    role?: 'superadmin' | 'client';
    home?: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}
