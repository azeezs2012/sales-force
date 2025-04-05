<template>
  <Head title="Supplier Types" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div :class="{'dark': appearance === 'dark'}" class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 bg-white dark:bg-neutral-800">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <div class="absolute top-0 left-0 py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-700 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 text-gray-900 dark:text-neutral-100">
                <h2 class="text-2xl font-bold mb-4">Supplier Type Management</h2>

                <!-- Form to create or update a supplier type -->
                <form @submit.prevent="handleSubmit" class="mb-6">
                  <div class="flex items-center gap-4">
                    <input type="text" v-model="form.type_name" placeholder="Type Name" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" required />
                    <input type="checkbox" v-model="form.active" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Active
                    <input type="checkbox" v-model="form.approved" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Approved
                    <DropdownMenu>
                      <DropdownMenuTrigger class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100">
                        {{ form.parent ? supplierTypes.find(type => type.id === form.parent)?.type_name : 'Select Parent Type' }}
                      </DropdownMenuTrigger>
                      <DropdownMenuContent>
                        <DropdownMenuItem @click="form.parent = null">None</DropdownMenuItem>
                        <DropdownMenuItem v-for="type in supplierTypes" :key="type.id" @click="form.parent = type.id">
                          {{ type.type_name }}
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                    <button type="submit" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">{{ isEditing ? 'Update' : 'Create' }} Supplier Type</button>
                  </div>
                </form>

                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Type Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Created By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Updated By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-700 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="supplierType in supplierTypes" :key="supplierType.id">
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          <span :style="{ paddingLeft: `${getIndentationLevel(supplierType)}rem` }">
                            <span v-if="supplierType.parent">• </span>{{ supplierType.type_name }}
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ supplierType.active ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ supplierType.approved ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ supplierType.approver ? supplierType.approver.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ supplierType.creator ? supplierType.creator.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ supplierType.updater ? supplierType.updater.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <DropdownMenu>
                            <DropdownMenuTrigger class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Select Action</DropdownMenuTrigger>
                            <DropdownMenuContent>
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem @click="() => editSupplierType(supplierType)">Edit</DropdownMenuItem>
                              <DropdownMenuItem @click="() => deleteSupplierType(supplierType.id)">Delete</DropdownMenuItem>
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

const { toast } = useToast()

const breadcrumbs = [
  {
    title: 'Supplier Types',
    href: '/supplier-types',
  },
];

interface SupplierType {
  id: string;
  type_name: string;
  active: boolean;
  approved: boolean;
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
  parent?: string;
}

const supplierTypes: Ref<SupplierType[]> = ref([]);
const form: Ref<Partial<SupplierType>> = ref({
  id: undefined,
  type_name: '',
  active: true,
  approved: true,
  parent: undefined,
});
const isEditing = ref(false);

const { appearance, updateAppearance } = useAppearance();

const fetchSupplierTypes = async () => {
  try {
    const response = await axios.get('/api/supplier-types');
    supplierTypes.value = sortSupplierTypesHierarchically(response.data);
  } catch (error) {
    toast({
      title: 'Error',
      description: 'Failed to fetch supplier types.',
      variant: 'destructive',
    });
  }
};

const sortSupplierTypesHierarchically = (types: SupplierType[]): SupplierType[] => {
  const typeMap = new Map<string, SupplierType>();
  types.forEach(type => typeMap.set(type.id, type));

  const sortedTypes: SupplierType[] = [];

  const addTypeWithChildren = (type: SupplierType) => {
    sortedTypes.push(type);
    const children = types.filter(t => t.parent === type.id);
    children.forEach(addTypeWithChildren);
  };

  types.filter(type => !type.parent).forEach(addTypeWithChildren);

  return sortedTypes;
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await updateSupplierType();
    } else {
      await createSupplierType();
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

const createSupplierType = async () => {
  try {
    await axios.post('/api/supplier-types', form.value);
    fetchSupplierTypes();
    resetForm();
    toast({
      title: 'Success',
      description: 'Supplier type created successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to create supplier type.',
      variant: 'destructive',
    });
  }
};

const editSupplierType = (supplierType: SupplierType) => {
  form.value = { ...supplierType };
  isEditing.value = true;
};

const updateSupplierType = async () => {
  try {
    await axios.put(`/api/supplier-types/${form.value.id}`, form.value);
    fetchSupplierTypes();
    resetForm();
    toast({
      title: 'Success',
      description: 'Supplier type updated successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to update supplier type.',
      variant: 'destructive',
    });
  }
};

const deleteSupplierType = async (id: string) => {
  try {
    await axios.delete(`/api/supplier-types/${id}`);
    fetchSupplierTypes();
    toast({
      title: 'Success',
      description: 'Supplier type deleted successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete supplier type.',
      variant: 'destructive',
    });
  }
};

const resetForm = () => {
  form.value = { id: undefined, type_name: '', active: true, approved: true, parent: undefined };
  isEditing.value = false;
};

const getIndentationLevel = (supplierType: SupplierType): number => {
  let level = 0;
  let currentType = supplierType;
  while (currentType.parent) {
    level++;
    currentType = supplierTypes.value.find(t => t.id === currentType.parent) || currentType;
  }
  return level * 1.5; // Adjust multiplier for desired indentation
};

onMounted(() => {
  fetchSupplierTypes();
});
</script>

<style scoped>
/* Add your styles here */
</style> 