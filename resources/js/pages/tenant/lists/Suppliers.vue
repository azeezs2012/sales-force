<template>
  <Head title="Suppliers" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div :class="{'dark': appearance === 'dark'}" class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 bg-white dark:bg-neutral-800">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <div class="absolute top-0 left-0 py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-700 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 text-gray-900 dark:text-neutral-100">
                <h2 class="text-2xl font-bold mb-4">Supplier Management</h2>

                <!-- Form to create or update a supplier -->
                <form @submit.prevent="handleSubmit" class="mb-6">
                  <!-- Supplier Details -->
                  <div class="flex flex-wrap items-center gap-4 border-b pb-4">
                    <h3 class="text-lg font-semibold w-full">Supplier Details</h3>
                    <input type="text" v-model="form.supplier_code" placeholder="Supplier Code" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" required />
                    <input type="text" v-model="form.phone_no" placeholder="Phone Number" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" />
                    <input type="checkbox" v-model="form.active" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Active
                    <input type="checkbox" v-model="form.approved" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Approved
                  </div>

                  <!-- User Account Details -->
                  <div class="flex flex-wrap items-center gap-4 border-b pb-4">
                    <h3 class="text-lg font-semibold w-full">User Account Details</h3>
                    <input type="text" v-model="form.name" placeholder="Name" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100 w-60" required />
                    <input type="email" v-model="form.email" placeholder="Email" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100 w-60" required />
                    <div class="relative w-60">
                      <Input :type="showPassword ? 'text' : 'password'" v-model="form.password" placeholder="Password" class="pr-10 w-full" />
                      <Button type="button" @click="togglePasswordVisibility" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                        👁️
                      </Button>
                    </div>
                    <Button type="button" @click="generateStrongPassword" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Generate Password</Button>
                  </div>

                  <!-- Payment Term Details -->
                  <div class="flex flex-wrap items-center gap-4 border-b pb-4">
                    <h3 class="text-lg font-semibold w-full">Payment Term</h3>
                    <select v-model="form.default_payment_term" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100">
                      <option :value="null">None</option>
                      <option v-for="term in paymentTerms" :key="term.id" :value="term.id">{{ term.payment_term_name }}</option>
                    </select>
                  </div>

                  <!-- Payment Method Details -->
                  <div class="flex flex-wrap items-center gap-4 border-b pb-4">
                    <h3 class="text-lg font-semibold w-full">Payment Method</h3>
                    <select v-model="form.default_payment_method" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100">
                      <option :value="null">None</option>
                      <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.method_name }}</option>
                    </select>
                  </div>

                  <button type="submit" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">{{ isEditing ? 'Update' : 'Create' }} Supplier</button>
                </form>

                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Supplier Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Phone Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Default Payment Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Default Payment Term</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-700 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="supplier in suppliers" :key="supplier.id">
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ supplier.user?.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ supplier.user?.email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ supplier.supplier_code }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ supplier.phone_no }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ supplier.default_payment_method?.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ supplier.default_payment_term?.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ supplier.active ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ supplier.approved ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <DropdownMenu>
                            <DropdownMenuTrigger class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Select Action</DropdownMenuTrigger>
                            <DropdownMenuContent>
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem @click="() => editSupplier(supplier)">Edit</DropdownMenuItem>
                              <DropdownMenuItem @click="() => deleteSupplier(supplier.id)">Delete</DropdownMenuItem>
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
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';

const { toast } = useToast()

const breadcrumbs = [
  {
    title: 'Suppliers',
    href: '/suppliers',
  },
];

interface Supplier {
  id: string;
  supplier_code: string;
  phone_no?: string;
  default_payment_method?: { id: string; name: string };
  default_payment_term?: { id: string; name: string };
  user?: { id: string; name: string; email: string };
  active: boolean;
  approved: boolean;
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
}

interface PaymentMethod {
  id: string;
  name: string;
  method_name: string;
}

interface PaymentTerm {
  id: string;
  name: string;
  payment_term_name: string;
}

const suppliers: Ref<Supplier[]> = ref([]);
const paymentMethods: Ref<PaymentMethod[]> = ref([]);
const paymentTerms: Ref<PaymentTerm[]> = ref([]);
const form: Ref<Partial<Supplier> & { name?: string; email?: string; password?: string }> = ref({
  id: undefined,
  name: '',
  email: '',
  password: '',
  supplier_code: '',
  phone_no: '',
  default_payment_method: null,
  default_payment_term: null,
  active: true,
  approved: true,
});
const isEditing = ref(false);
const showPassword = ref(false);

const { appearance, updateAppearance } = useAppearance();

const fetchSuppliers = async () => {
  try {
    const response = await axios.get('/api/suppliers');
    suppliers.value = response.data;
  } catch (error) {
    toast({
      title: 'Error',
      description: 'Failed to fetch suppliers.',
      variant: 'destructive',
    });
  }
};

const fetchPaymentMethods = async () => {
  try {
    const response = await axios.get('/api/payment-methods');
    paymentMethods.value = response.data;
  } catch (error) {
    toast({
      title: 'Error',
      description: 'Failed to fetch payment methods.',
      variant: 'destructive',
    });
  }
};

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
      await updateSupplier();
    } else {
      await createSupplier();
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

const createSupplier = async () => {
  try {
    await axios.post('/api/suppliers', form.value);
    fetchSuppliers();
    resetForm();
    toast({
      title: 'Success',
      description: 'Supplier created successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to create supplier.',
      variant: 'destructive',
    });
  }
};

const editSupplier = (supplier: Supplier) => {
  form.value = {
    id: supplier.id,
    name: supplier.user?.name,
    email: supplier.user?.email,
    supplier_code: supplier.supplier_code,
    phone_no: supplier.phone_no,
    default_payment_method: supplier.default_payment_method?.id,
    default_payment_term: supplier.default_payment_term?.id,
    active: supplier.active,
    approved: supplier.approved,
  };
  isEditing.value = true;
};

const updateSupplier = async () => {
  try {
    await axios.put(`/api/suppliers/${form.value.id}`, form.value);
    fetchSuppliers();
    resetForm();
    toast({
      title: 'Success',
      description: 'Supplier updated successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to update supplier.',
      variant: 'destructive',
    });
  }
};

const deleteSupplier = async (id: string) => {
  try {
    await axios.delete(`/api/suppliers/${id}`);
    fetchSuppliers();
    toast({
      title: 'Success',
      description: 'Supplier deleted successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete supplier.',
      variant: 'destructive',
    });
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    name: '',
    email: '',
    password: '',
    supplier_code: '',
    phone_no: '',
    default_payment_method: null,
    default_payment_term: null,
    active: true,
    approved: true,
  };
  isEditing.value = false;
};

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const generateStrongPassword = () => {
  const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=';
  let password = '';
  for (let i = 0, n = charset.length; i < 12; ++i) {
    password += charset.charAt(Math.floor(Math.random() * n));
  }
  form.value.password = password;
};

onMounted(() => {
  fetchSuppliers();
  fetchPaymentMethods();
  fetchPaymentTerms();
});
</script>

<style scoped>
/* Add your styles here */
</style> 