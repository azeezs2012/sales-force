<template>
  <Head title="Branches" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div :class="{'dark': appearance === 'dark'}" class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 bg-white dark:bg-neutral-800">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <div class="absolute top-0 left-0 py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-700 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 text-gray-900 dark:text-neutral-100">
                <h2 class="text-2xl font-bold mb-4">Branch Management</h2>

                <!-- Form to create or update a branch -->
                <form @submit.prevent="handleSubmit" class="mb-6">
                  <div class="flex items-center gap-4">
                    <input type="text" v-model="form.branch_name" placeholder="Branch Name" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" required />
                    <input type="checkbox" v-model="form.active" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Active
                    <input type="checkbox" v-model="form.approved" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Approved
                    <select v-model="form.parent" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100">
                      <option value="" disabled>Select Parent Branch</option>
                      <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                        {{ branch.branch_name }}
                      </option>
                    </select>
                    <button type="submit" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">{{ isEditing ? 'Update' : 'Create' }} Branch</button>
                  </div>
                </form>

                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Branch Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-700 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="branch in branches" :key="branch.id">
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ branch.branch_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ branch.active ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ branch.approved ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <DropdownMenu>
                            <DropdownMenuTrigger class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Select Action</DropdownMenuTrigger>
                            <DropdownMenuContent>
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem @click="() => editBranch(branch)">Edit</DropdownMenuItem>
                              <DropdownMenuItem @click="() => deleteBranch(branch.id)">Delete</DropdownMenuItem>
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
import { ref, onMounted } from 'vue';
import axios from 'axios';
import type { Ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const { toast } = useToast()

const breadcrumbs = [
  {
    title: 'Branches',
    href: '/branches',
  },
];

interface Branch {
  id: string;
  branch_name: string;
  active: boolean;
  approved: boolean;
  parent?: string;
}

const branches: Ref<Branch[]> = ref([]);
const form: Ref<Partial<Branch>> = ref({
  id: undefined,
  branch_name: '',
  active: false,
  approved: false,
  parent: undefined,
});
const isEditing = ref(false);

const { appearance, updateAppearance } = useAppearance();

const fetchActiveApprovedBranches = async () => {
  try {
    const response = await axios.get('/api/branches');
    branches.value = response.data.filter((branch: Branch) => branch.active && branch.approved);
  } catch (error) {
    console.error('Failed to fetch active and approved branches:', error);
  }
};

const fetchBranches = async () => {
  try {
    const response = await axios.get('/api/branches');
    branches.value = response.data;
    fetchActiveApprovedBranches();
  } catch (error) {
    console.error('Failed to fetch branches:', error);
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await updateBranch();
    } else {
      await createBranch();
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

const createBranch = async () => {
  try {
    await axios.post('/api/branches', form.value);
    fetchBranches();
    resetForm();
    toast({
      title: 'Success',
      description: 'Branch created successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to create branch.',
      variant: 'destructive',
    });
  }
};

const editBranch = (branch: Branch) => {
  form.value = { ...branch };
  isEditing.value = true;
};

const updateBranch = async () => {
  try {
    await axios.put(`/api/branches/${form.value.id}`, form.value);
    fetchBranches();
    resetForm();
    toast({
      title: 'Success',
      description: 'Branch updated successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to update branch.',
      variant: 'destructive',
    });
  }
};

const deleteBranch = async (id: string) => {
  try {
    await axios.delete(`/api/branches/${id}`);
    fetchBranches();
    toast({
      title: 'Success',
      description: 'Branch deleted successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete branch.',
      variant: 'destructive',
    });
  }
};

const resetForm = () => {
  form.value = { id: undefined, branch_name: '', active: false, approved: false, parent: undefined };
  isEditing.value = false;
};

onMounted(() => {
  fetchBranches();
});
</script>

<style scoped>
/* Add your styles here */
</style> 