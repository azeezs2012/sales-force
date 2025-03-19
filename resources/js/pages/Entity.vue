<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { ref } from 'vue';
import axios from 'axios';

defineProps<{
    tenants: {
        id: string;
        domain: string;
        created_at: string;
        updated_at: string;
        selectedAction?: string;
    }[]
}>();

const breadcrumbs = [
    {
        title: 'Entities',
        href: '/entities',
    },
];

const executeAction = async (tenant: { id: string; selectedAction?: string }) => {
    if (!tenant.selectedAction) {
        alert('Please select an action.');
        return;
    }

    const action = tenant.selectedAction;
    const url = `/api/entity/${tenant.id}/${action}`;

    try {
        let method = 'post';
        if (action === 'delete') {
            method = 'delete';
        }
        await axios({
            method,
            url,
        });
        alert(`Action ${action} executed successfully for tenant ${tenant.id}.`);
    } catch (error) {
        console.error(error);
        alert(`Failed to execute action ${action} for tenant ${tenant.id}.`);
    }
};
</script>

<template>
    <Head title="Entities" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <div class="py-12">
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900">
                                <h2 class="text-2xl font-bold mb-4">All Entities</h2>
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated At</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="tenant in tenants" :key="tenant.id">
                                                <td class="px-6 py-4 whitespace-nowrap">{{ tenant.id }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">{{ tenant.domain }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">{{ tenant.created_at }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">{{ tenant.updated_at }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <select v-model="tenant.selectedAction" class="mr-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                        <option value="" disabled selected>Select Action</option>
                                                        <option value="migrate">Migrate</option>
                                                        <option value="flushdb">FlushDB</option>
                                                        <option value="rollback">Rollback</option>
                                                        <option value="delete">Delete</option>
                                                    </select>
                                                    <button @click="executeAction(tenant)" class="bg-blue-500 text-white px-4 py-2 rounded">Execute</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
