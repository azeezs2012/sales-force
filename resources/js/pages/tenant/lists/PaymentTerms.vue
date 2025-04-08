<template>
  <Head title="Payment Terms" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div :class="{'dark': appearance === 'dark'}" class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 bg-white dark:bg-neutral-800">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <div class="absolute top-0 left-0 py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-700 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 text-gray-900 dark:text-neutral-100">
                <h2 class="text-2xl font-bold mb-4">Payment Term Management</h2>

                <!-- Form to create or update a payment term -->
                <form @submit.prevent="handleSubmit" class="mb-6">
                  <div class="flex items-center gap-4">
                    <input type="text" v-model="form.payment_term_name" placeholder="Term Name" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" required />
                    <input type="number" v-model="form.duration_count" placeholder="Duration Count" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" required />
                    <select v-model="form.duration_unit" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" required>
                      <option value="days">Days</option>
                      <option value="months">Months</option>
                    </select>
                    <input type="checkbox" v-model="form.active" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Active
                    <input type="checkbox" v-model="form.approved" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Approved
                    <button type="submit" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">{{ isEditing ? 'Update' : 'Create' }} Payment Term</button>
                  </div>
                </form>

                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Term Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Created By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Updated By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-700 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="term in paymentTerms" :key="term.id">
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ term.payment_term_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ term.duration_count }} {{ term.duration_unit }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ term.active ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ term.approved ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ term.creator ? term.creator.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ term.updater ? term.updater.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ term.approver ? term.approver.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <DropdownMenu>
                            <DropdownMenuTrigger class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Select Action</DropdownMenuTrigger>
                            <DropdownMenuContent>
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem @click="() => editPaymentTerm(term)">Edit</DropdownMenuItem>
                              <DropdownMenuItem @click="() => deletePaymentTerm(term.id)">Delete</DropdownMenuItem>
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
    title: 'Payment Terms',
    href: '/payment-terms',
  },
];

interface PaymentTerm {
  id: string;
  payment_term_name: string;
  duration_count: number;
  duration_unit: string;
  active: boolean;
  approved: boolean;
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
}

const paymentTerms: Ref<PaymentTerm[]> = ref([]);
const form: Ref<Partial<PaymentTerm>> = ref({
  id: undefined,
  payment_term_name: '',
  duration_count: 0,
  duration_unit: 'days',
  active: true,
  approved: false,
});
const isEditing = ref(false);

const { appearance, updateAppearance } = useAppearance();

const fetchPaymentTerms = async () => {
  try {
    const response = await axios.get('/api/payment-terms');
    paymentTerms.value = response.data;
  } catch (error) {
    toast({
      title: 'Error',
      description: 'Failed to fetch payment terms.',
      variant: 'destructive',
    });
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await updatePaymentTerm();
    } else {
      await createPaymentTerm();
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

const createPaymentTerm = async () => {
  try {
    await axios.post('/api/payment-terms', form.value);
    fetchPaymentTerms();
    resetForm();
    toast({
      title: 'Success',
      description: 'Payment term created successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to create payment term.',
      variant: 'destructive',
    });
  }
};

const editPaymentTerm = (term: PaymentTerm) => {
  form.value = { ...term };
  isEditing.value = true;
};

const updatePaymentTerm = async () => {
  try {
    await axios.put(`/api/payment-terms/${form.value.id}`, form.value);
    fetchPaymentTerms();
    resetForm();
    toast({
      title: 'Success',
      description: 'Payment term updated successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to update payment term.',
      variant: 'destructive',
    });
  }
};

const deletePaymentTerm = async (id: string) => {
  try {
    await axios.delete(`/api/payment-terms/${id}`);
    fetchPaymentTerms();
    toast({
      title: 'Success',
      description: 'Payment term deleted successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete payment term.',
      variant: 'destructive',
    });
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    payment_term_name: '',
    duration_count: 0,
    duration_unit: 'days',
    active: true,
    approved: false,
  };
  isEditing.value = false;
};

onMounted(() => {
  fetchPaymentTerms();
});
</script>

<style scoped>
/* Add styles for the component */
</style> 