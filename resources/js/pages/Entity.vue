<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { 
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table';

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

const newTenantId = ref('');
const tenants = ref<{ id: string; domain: string; created_at: string; updated_at: string; selectedAction?: string }[]>([]);

const fetchTenants = async () => {
    try {
        const response = await axios.get('/api/entity/all');
        console.log(response.data);
        tenants.value = response.data.tenants;
    } catch (error) {
        console.error('Failed to fetch tenants:', error);
    }
};

onMounted(() => {
    fetchTenants();
});

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
        fetchTenants();
    } catch (error) {
        console.error(`Failed to execute action ${action} for tenant ${tenant.id}:`, error);
        alert(`Failed to execute action ${action} for tenant ${tenant.id}.`);
    }
};

const createTenant = async () => {
    try {
        await axios.post('/api/entity/create', {
            id: newTenantId.value,
        });
        alert('Tenant created successfully!');
        newTenantId.value = '';
        fetchTenants();
    } catch (error) {
        console.error('Failed to create tenant:', error);
        alert('Failed to create tenant.');
    }
};
</script>

<template>
    <Head title="Entities" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <Card class="border-0 shadow-none">
                    <CardHeader>
                        <CardTitle>All Entities</CardTitle>
                    </CardHeader>
                    
                    <CardContent>
                        <!-- Form to create a new tenant -->
                        <form @submit.prevent="createTenant" class="mb-6 space-y-4">
                            <div class="flex items-center gap-4">
                                <Input 
                                    v-model="newTenantId" 
                                    placeholder="Enter Tenant ID" 
                                    class="max-w-sm" 
                                    required 
                                />
                                <Button type="submit">Create Entity</Button>
                            </div>
                        </form>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>Domain</TableHead>
                                    <TableHead>Created At</TableHead>
                                    <TableHead>Updated At</TableHead>
                                    <TableHead>Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="tenant in tenants" :key="tenant.id">
                                    <TableCell>{{ tenant.id }}</TableCell>
                                    <TableCell>{{ tenant.domain }}</TableCell>
                                    <TableCell>{{ tenant.created_at }}</TableCell>
                                    <TableCell>{{ tenant.updated_at }}</TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="outline">Select Action</Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent>
                                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem @click="() => executeAction({ ...tenant, selectedAction: 'migrate' })">Migrate</DropdownMenuItem>
                                                <DropdownMenuItem @click="() => executeAction({ ...tenant, selectedAction: 'flushdb' })">FlushDB</DropdownMenuItem>
                                                <DropdownMenuItem @click="() => executeAction({ ...tenant, selectedAction: 'rollback' })">Rollback</DropdownMenuItem>
                                                <DropdownMenuItem @click="() => executeAction({ ...tenant, selectedAction: 'delete' })">Delete</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
