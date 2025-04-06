<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarGroup } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, ChevronDown, Building2, Users, MapPin, ShoppingCart, CreditCard, Package, Boxes, FileText, Truck } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { ref } from 'vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Users',
        href: '/users',
        icon: Users,
    },
];

const listNavItems = {
    organization: {
        title: 'Organization',
        icon: Building2,
        items: [
            {
                title: 'Branches',
                href: '/branches',
                icon: Building2,
            },
            {
                title: 'Sales Reps',
                href: '/sales-reps',
                icon: Users,
            },
            {
                title: 'Locations',
                href: '/locations',
                icon: MapPin,
            },
        ],
    },
    customers: {
        title: 'Customers',
        icon: Users,
        items: [
            {
                title: 'Customer Types',
                href: '/customer-types',
                icon: Users,
            },
            {
                title: 'Customers',
                href: '/customers',
                icon: Users,
            },
        ],
    },
    suppliers: {
        title: 'Suppliers',
        icon: Truck,
        items: [
            {
                title: 'Supplier Types',
                href: '/supplier-types',
                icon: Truck,
            },
            {
                title: 'Suppliers',
                href: '/suppliers',
                icon: Truck,
            },
        ],
    },
    products: {
        title: 'Products',
        icon: Package,
        items: [
            {
                title: 'Product Types',
                href: '/product-types',
                icon: Package,
            },
            {
                title: 'Product Categories',
                href: '/product-categories',
                icon: Boxes,
            },
        ],
    },
    payments: {
        title: 'Payments',
        icon: CreditCard,
        items: [
            {
                title: 'Payment Methods',
                href: '/payment-methods',
                icon: CreditCard,
            },
            {
                title: 'Payment Terms',
                href: '/payment-terms',
                icon: FileText,
            },
        ],
    },
};

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

const expandedGroups = ref<string[]>([]);

const toggleGroup = (groupKey: string) => {
    const index = expandedGroups.value.indexOf(groupKey);
    if (index === -1) {
        expandedGroups.value.push(groupKey);
    } else {
        expandedGroups.value.splice(index, 1);
    }
};
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
            
            <SidebarMenu>
                <template v-for="(group, key) in listNavItems" :key="key">
                    <SidebarGroup>
                        <SidebarMenuButton @click="toggleGroup(key)">
                            <component :is="group.icon" class="h-4 w-4" />
                            <span>{{ group.title }}</span>
                            <ChevronDown 
                                class="h-4 w-4 transition-transform duration-200" 
                                :class="{ 'rotate-180': expandedGroups.includes(key) }" 
                            />
                        </SidebarMenuButton>
                        <SidebarMenu v-show="expandedGroups.includes(key)">
                            <SidebarMenuItem v-for="item in group.items" :key="item.href">
                                <SidebarMenuButton as-child>
                                    <Link :href="item.href">
                                        <component :is="item.icon" class="h-4 w-4" />
                                        <span>{{ item.title }}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroup>
                </template>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
