<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Users',
        href: '/users',
        icon: LayoutGrid,
    },
    {
        title: 'Branches',
        href: '/branches',
        icon: LayoutGrid,
    },
    {
        title: 'Sales Reps',
        href: '/sales-reps',
        icon: LayoutGrid,
    },
    {
        title: 'Locations',
        href: '/locations',
        icon: LayoutGrid,
    },
    {
        title: 'Customer Types',
        href: '/customer-types',
        icon: LayoutGrid,
    },
    {
        title: 'Supplier Types',
        href: '/supplier-types',
        icon: LayoutGrid,
    },
    {
        title: 'Payment Methods',
        href: '/payment-methods',
        icon: LayoutGrid,
    },
    {
        title: 'Product Types',
        href: '/product-types',
        icon: LayoutGrid,
    },
    {
        title: 'Product Categories',
        href: '/product-categories',
        icon: LayoutGrid,
    },
    {
        title: 'Payment Terms',
        href: '/payment-terms',
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
