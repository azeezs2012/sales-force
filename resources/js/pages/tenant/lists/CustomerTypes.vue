<template>
  <Head title="Customer Types" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div :class="{'dark': appearance === 'dark'}" class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 bg-white dark:bg-neutral-800">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <div class="absolute top-0 left-0 py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-700 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 text-gray-900 dark:text-neutral-100">
                <h2 class="text-2xl font-bold mb-4">Customer Type Management</h2>

                <!-- Form to create or update a customer type -->
                <form @submit.prevent="handleSubmit" class="mb-6">
                  <div class="flex items-center gap-4">
                    <input type="text" v-model="form.type_name" placeholder="Type Name" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" required />
                    <input type="checkbox" v-model="form.active" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Active
                    <input type="checkbox" v-model="form.approved" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Approved
                    <DropdownMenu>
                      <DropdownMenuTrigger class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100">
                        {{ form.parent ? customerTypes.find(type => type.id === form.parent)?.type_name : 'Select Parent Type' }}
                      </DropdownMenuTrigger>
                      <DropdownMenuContent>
                        <DropdownMenuItem v-for="type in customerTypes" :key="type.id" @click="form.parent = type.id">
                          {{ type.type_name }}
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                    <button type="submit" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">{{ isEditing ? 'Update' : 'Create' }} Customer Type</button>
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
                      <tr v-for="customerType in customerTypes" :key="customerType.id">
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          <span :style="{ paddingLeft: `${getIndentationLevel(customerType)}rem` }">
                            <span v-if="customerType.parent">• </span>{{ customerType.type_name }}
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ customerType.active ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ customerType.approved ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ customerType.approver ? customerType.approver.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ customerType.creator ? customerType.creator.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ customerType.updater ? customerType.updater.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <DropdownMenu>
                            <DropdownMenuTrigger class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Select Action</DropdownMenuTrigger>
                            <DropdownMenuContent>
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem @click="() => editCustomerType(customerType)">Edit</DropdownMenuItem>
                              <DropdownMenuItem @click="() => deleteCustomerType(customerType.id)">Delete</DropdownMenuItem>
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
    title: 'Customer Types',
    href: '/customer-types',
  },
];

interface CustomerType {
  id: string;
  type_name: string;
  active: boolean;
  approved: boolean;
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
  parent?: string;
}

const customerTypes: Ref<CustomerType[]> = ref([]);
const form: Ref<Partial<CustomerType>> = ref({
  id: undefined,
  type_name: '',
  active: true,
  approved: true,
  parent: undefined,
});
const isEditing = ref(false);

const { appearance, updateAppearance } = useAppearance();

const sortCustomerTypesHierarchically = (types: CustomerType[]): CustomerType[] => {
  const typeMap = new Map<string, CustomerType>();
  types.forEach(type => typeMap.set(type.id, type));

  const sortedTypes: CustomerType[] = [];

  const addTypeWithChildren = (type: CustomerType) => {
    sortedTypes.push(type);
    const children = types.filter(t => t.parent === type.id);
    children.forEach(addTypeWithChildren);
  };

  types.filter(type => !type.parent).forEach(addTypeWithChildren);

  return sortedTypes;
};

const fetchCustomerTypes = async () => {
  try {
    const response = await axios.get('/api/customer-types');
    const allTypes = response.data;
    customerTypes.value = sortCustomerTypesHierarchically(allTypes);
  } catch (error) {
    toast({
      title: 'Error',
      description: 'Failed to fetch customer types.',
      variant: 'destructive',
    });
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await updateCustomerType();
    } else {
      await createCustomerType();
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

const createCustomerType = async () => {
  try {
    await axios.post('/api/customer-types', form.value);
    fetchCustomerTypes();
    resetForm();
    toast({
      title: 'Success',
      description: 'Customer type created successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to create customer type.',
      variant: 'destructive',
    });
  }
};

const editCustomerType = (customerType: CustomerType) => {
  form.value = { ...customerType };
  isEditing.value = true;
};

const updateCustomerType = async () => {
  try {
    await axios.put(`/api/customer-types/${form.value.id}`, form.value);
    fetchCustomerTypes();
    resetForm();
    toast({
      title: 'Success',
      description: 'Customer type updated successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to update customer type.',
      variant: 'destructive',
    });
  }
};

const deleteCustomerType = async (id: string) => {
  try {
    await axios.delete(`/api/customer-types/${id}`);
    fetchCustomerTypes();
    toast({
      title: 'Success',
      description: 'Customer type deleted successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete customer type.',
      variant: 'destructive',
    });
  }
};

const resetForm = () => {
  form.value = { id: undefined, type_name: '', active: true, approved: true, parent: undefined };
  isEditing.value = false;
};

const getIndentationLevel = (customerType: CustomerType): number => {
  let level = 0;
  let currentType = customerType;
  while (currentType.parent) {
    level++;
    currentType = customerTypes.value.find(t => t.id === currentType.parent) || currentType;
  }
  return level * 1.5; // Adjust multiplier for desired indentation
};

onMounted(() => {
  fetchCustomerTypes();
});
</script>

<style scoped>
/* Add your styles here */
</style> 