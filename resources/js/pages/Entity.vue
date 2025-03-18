<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    tenants: {
        id: string;
        domain: string;
        created_at: string;
        updated_at: string;
    }[]
}>();
</script>

<template>
    <Head title="Entities" />

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
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="tenant in tenants" :key="tenant.id">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ tenant.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ tenant.domain }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ tenant.created_at }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ tenant.updated_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
