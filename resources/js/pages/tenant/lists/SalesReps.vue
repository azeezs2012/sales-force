<template>
  <Head title="Sales Representatives" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div :class="{'dark': appearance === 'dark'}" class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 bg-white dark:bg-neutral-800">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <div class="absolute top-0 left-0 py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-700 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 text-gray-900 dark:text-neutral-100">
                <h2 class="text-2xl font-bold mb-4">Sales Representative Management</h2>

                <!-- Form to create or update a sales representative -->
                <form @submit.prevent="handleSubmit" class="mb-6">
                  <div class="flex flex-wrap items-center gap-4">
                    <div class="flex gap-4 w-full">
                      <input type="text" v-model="form.code" placeholder="Sales Rep Code" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100 w-60" required />
                      <input type="text" v-model="form.name" placeholder="Name" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100 w-60" required />
                    </div>
                    <div class="flex gap-4 w-full">
                      <input type="email" v-model="form.email" placeholder="Email" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100 w-60" required />
                      <div class="relative w-60">
                        <Input :type="showPassword ? 'text' : 'password'" v-model="form.password" placeholder="Password" class="pr-10 w-full" required />
                        <Button type="button" @click="togglePasswordVisibility" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                          👁️
                        </Button>
                      </div>
                      <Button type="button" @click="generateStrongPassword" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Generate Password</Button>
                    </div>
                    <div class="flex gap-4 w-full items-center">
                      <DropdownMenu>
                        <DropdownMenuTrigger class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100">
                          {{ form.parent ? salesReps.find(salesRep => salesRep.id === form.parent)?.code : 'Select Parent Sales Rep' }}
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                          <DropdownMenuItem @click="form.parent = null">None</DropdownMenuItem>
                          <DropdownMenuItem v-for="salesRep in salesReps" :key="salesRep.id" @click="form.parent = salesRep.id">
                            {{ ' '.repeat(getIndentationLevel(salesRep) * 2) + salesRep.code }}
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                      <input type="checkbox" v-model="form.active" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Active
                      <input type="checkbox" v-model="form.approved" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Approved
                      
                      <Button type="submit" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">{{ isEditing ? 'Update' : 'Create' }} Sales Rep</Button>
                    </div>
                  </div>
                </form>

                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Created By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Updated By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-700 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="salesRep in salesReps" :key="salesRep.id">
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          <span :style="{ paddingLeft: `${getIndentationLevel(salesRep)}rem` }">
                            <span v-if="salesRep.parent">• </span>{{ salesRep.code }}
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ salesRep.user?.email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ salesRep.active ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ salesRep.approved ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ salesRep.approver ? salesRep.approver.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ salesRep.creator ? salesRep.creator.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ salesRep.updater ? salesRep.updater.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <DropdownMenu>
                            <DropdownMenuTrigger class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Select Action</DropdownMenuTrigger>
                            <DropdownMenuContent>
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem @click="() => editSalesRep(salesRep)">Edit</DropdownMenuItem>
                              <DropdownMenuItem @click="() => deleteSalesRep(salesRep.id?.toString() || '')">Delete</DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
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

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/TenantAppLayout.vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useToast } from '@/components/ui/toast/use-toast';
import { ref, onMounted, defineComponent, h, VNode } from 'vue';
import axios from 'axios';
import type { Ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';

const { toast } = useToast()

const breadcrumbs = [
  {
    title: 'Sales Representatives',
    href: '/sales-reps',
  },
];

interface SalesRep {
  id?: number;
  name?: string;
  code?: string;
  email?: string;
  password?: string;
  active?: boolean;
  approved?: boolean;
  parent?: number | null;
  childSalesReps?: SalesRep[];
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
  user?: { name: string; email: string };
}

const salesReps: Ref<SalesRep[]> = ref([]);
const form: Ref<Partial<SalesRep>> = ref({
  id: undefined,
  name: '',
  code: '',
  email: '',
  password: '',
  active: true,
  approved: true,
  parent: undefined,
});
const isEditing = ref(false);

const { appearance, updateAppearance } = useAppearance();

const showPassword = ref(false);

const fetchActiveApprovedSalesReps = async () => {
  try {
    const response = await axios.get('/api/sales-reps');
    const allSalesReps = sortSalesRepsHierarchically(response.data);
    salesReps.value = allSalesReps.filter((salesRep: SalesRep) => salesRep.active && salesRep.approved);
  } catch (error) {
    console.error('Failed to fetch active and approved sales representatives:', error);
  }
};

const sortSalesRepsHierarchically = (salesReps: SalesRep[]): SalesRep[] => {
  const salesRepMap = new Map<string, SalesRep>();
  salesReps.forEach(salesRep => salesRepMap.set(salesRep.id?.toString() || '', salesRep));

  const sortedSalesReps: SalesRep[] = [];

  const addSalesRepWithChildren = (salesRep: SalesRep) => {
    sortedSalesReps.push(salesRep);
    const children = salesReps.filter(s => s.parent === salesRep.id);
    children.forEach(addSalesRepWithChildren);
  };

  salesReps.filter(salesRep => !salesRep.parent).forEach(addSalesRepWithChildren);

  return sortedSalesReps;
};

const fetchSalesReps = async () => {
  try {
    const response = await axios.get('/api/sales-reps');
    const allSalesReps = response.data;
    salesReps.value = sortSalesRepsHierarchically(allSalesReps);
  } catch (error) {
    console.error('Failed to fetch sales representatives:', error);
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await updateSalesRep();
    } else {
      await createSalesRep();
    }
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'An error occurred.',
      variant: 'destructive',
    });
  }
};

const createSalesRep = async () => {
  try {
    await axios.post('/api/sales-reps', form.value);
    fetchSalesReps();
    resetForm();
    toast({
      title: 'Success',
      description: 'Sales representative created successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to create sales representative.',
      variant: 'destructive', 
    });
  }
};

const editSalesRep = (salesRep: SalesRep) => {
  form.value = {
    ...salesRep,
    name: salesRep.user?.name || '',
    email: salesRep.user?.email || '',
  };
  isEditing.value = true;
};

const updateSalesRep = async () => {
  try {
    if (form.value.id !== undefined) {
      await axios.put(`/api/sales-reps/${form.value.id.toString()}`, {
        ...form.value,
        parent: form.value.parent !== null && form.value.parent !== undefined ? form.value.parent.toString() : null,
      });
    }
    fetchSalesReps();
    resetForm();
    toast({
      title: 'Success',
      description: 'Sales representative updated successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to update sales representative.',
      variant: 'destructive',
    });
  }
};

const deleteSalesRep = async (id: string) => {
  try {
    await axios.delete(`/api/sales-reps/${id}`);
    fetchSalesReps();
    toast({
      title: 'Success',
      description: 'Sales representative deleted successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete sales representative.',
      variant: 'destructive',
    });
  }
};

const resetForm = () => {
  form.value = { id: undefined, code: '', active: true, approved: true, parent: undefined };
  isEditing.value = false;
};

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const generateStrongPassword = () => {
  const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=";
  let password = "";
  for (let i = 0, n = charset.length; i < 12; ++i) {
    password += charset.charAt(Math.floor(Math.random() * n));
  }
  form.value.password = password;
};

onMounted(() => {
  fetchSalesReps();
});

// Function to calculate indentation level based on hierarchy
const getIndentationLevel = (salesRep: SalesRep): number => {
  let level = 0;
  let currentSalesRep = salesRep;
  while (currentSalesRep.parent) {
    level++;
    currentSalesRep = salesReps.value.find(s => s.id === currentSalesRep.parent) || currentSalesRep;
  }
  return level * 1.5; // Adjust multiplier for desired indentation
};
</script>

<style scoped>
.relative {
  position: relative;
}
.absolute {
  position: absolute;
}
</style> 