<template>
  <Head title="Customers" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div :class="{'dark': appearance === 'dark'}" class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 bg-white dark:bg-neutral-800">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <div class="absolute top-0 left-0 py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-700 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 text-gray-900 dark:text-neutral-100">
                <h2 class="text-2xl font-bold mb-4">Customer Management</h2>

                <!-- Form to create or update a customer -->
                <form @submit.prevent="handleSubmit" class="mb-6">
                  <div class="flex flex-col gap-6">
                    <!-- Customer Details -->
                    <div class="flex flex-wrap items-center gap-4 border-b pb-4">
                      <h3 class="text-lg font-semibold w-full">Customer Details</h3>
                      <input type="text" v-model="form.customer_code" placeholder="Customer Code" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" required />
                      <input type="number" v-model="form.credit_limit" placeholder="Credit Limit" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" />
                      <input type="text" v-model="form.phone_no" placeholder="Phone Number" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" />
                      <select v-model="form.parent" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100">
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.customer_code }}</option>
                      </select>
                      <input type="checkbox" v-model="form.active" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Active
                      <input type="checkbox" v-model="form.approved" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Approved
                    </div>

                    <!-- User Account Details -->
                    <div class="flex flex-wrap items-center gap-4 border-b pb-4">
                      <h3 class="text-lg font-semibold w-full">User Account Details</h3>
                      <input type="text" v-model="form.name" placeholder="Name" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100 w-60" required />
                      <input type="email" v-model="form.email" placeholder="Email" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100 w-60" required />
                      <div class="relative w-60">
                        <Input :type="showPassword ? 'text' : 'password'" v-model="form.password" placeholder="Password" class="pr-10 w-full" required />
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
                        <option value="" disabled>Select Payment Term</option>
                        <option v-for="term in paymentTerms" :key="term.id" :value="term.id">{{ term.payment_term_name }}</option>
                      </select>
                    </div>

                    <!-- Payment Method Details -->
                    <div class="flex flex-wrap items-center gap-4 border-b pb-4">
                      <h3 class="text-lg font-semibold w-full">Payment Method</h3>
                      <select v-model="form.default_payment_method" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100">
                        <option value="" disabled>Select Payment Method</option>
                        <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.method_name }}</option>
                      </select>
                    </div>

                    <button type="submit" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">{{ isEditing ? 'Update' : 'Create' }} Customer</button>
                  </div>
                </form>

                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Customer Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Credit Limit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Phone Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-700 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="customer in customers" :key="customer.id">
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          <span :style="{ paddingLeft: `${getIndentationLevel(customer)}rem` }">
                            <span v-if="customer.parent">• </span>{{ customer.customer_code }}
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ customer.credit_limit }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ customer.phone_no }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ customer.active ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ customer.approved ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <DropdownMenu>
                            <DropdownMenuTrigger class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Select Action</DropdownMenuTrigger>
                            <DropdownMenuContent>
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem @click="() => editCustomer(customer)">Edit</DropdownMenuItem>
                              <DropdownMenuItem @click="() => deleteCustomer(customer.id)">Delete</DropdownMenuItem>
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
    title: 'Customers',
    href: '/customers',
  },
];

interface Customer {
  id: string;
  customer_code: string;
  credit_limit: number;
  phone_no: string;
  parent?: string | number | null;
  active: boolean;
  approved: boolean;
  default_payment_term?: number | null;
  default_payment_method?: number | null;
  user?: { name: string; email: string };
  password?: string;
}

const customers: Ref<Customer[]> = ref([]);
const form: Ref<Partial<Customer>> = ref({
  id: undefined,
  customer_code: '',
  credit_limit: 0,
  phone_no: '',
  parent: null,
  active: true,
  approved: false,
  default_payment_term: null,
  default_payment_method: null,
  name: '',
  email: '',
  password: '',
});
const isEditing = ref(false);

const { appearance, updateAppearance } = useAppearance();

const showPassword = ref(false);

const paymentTerms: Ref<any[]> = ref([]);
const paymentMethods: Ref<any[]> = ref([]);

const sortCustomersHierarchically = (customers: Customer[]): Customer[] => {
  const customerMap = new Map<string, Customer>();
  customers.forEach(customer => customerMap.set(customer.id.toString() || '', customer));

  const sortedCustomers: Customer[] = [];

  const addCustomerWithChildren = (customer: Customer) => {
    sortedCustomers.push(customer);
    const children = customers.filter(c => c.parent === customer.id);
    children.forEach(addCustomerWithChildren);
  };

  customers.filter(customer => !customer.parent).forEach(addCustomerWithChildren);

  return sortedCustomers;
};

const getIndentationLevel = (customer: Customer): number => {
  let level = 0;
  let currentCustomer = customer;
  while (currentCustomer.parent) {
    level++;
    currentCustomer = customers.value.find(c => c.id === currentCustomer.parent) || currentCustomer;
  }
  return level * 1.5; // Adjust multiplier for desired indentation
};

const fetchCustomers = async () => {
  try {
    const response = await axios.get('/api/customers');
    const allCustomers = response.data;
    customers.value = sortCustomersHierarchically(allCustomers);
    console.log(customers.value);
    
  } catch (error) {
    toast({
      title: 'Error',
      description: 'Failed to fetch customers.',
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

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await updateCustomer();
    } else {
      await createCustomer();
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

const createCustomer = async () => {
  try {
    await axios.post('/api/customers', form.value);
    fetchCustomers();
    resetForm();
    toast({
      title: 'Success',
      description: 'Customer created successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to create customer.',
      variant: 'destructive',
    });
  }
};

const editCustomer = (customer: Customer) => {
  form.value = { ...customer, name: customer.user?.name || '',
  email: customer.user?.email || '', };
  isEditing.value = true;
};

const updateCustomer = async () => {
  try {
    await axios.put(`/api/customers/${form.value.id}`, form.value);
    fetchCustomers();
    resetForm();
    toast({
      title: 'Success',
      description: 'Customer updated successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to update customer.',
      variant: 'destructive',
    });
  }
};

const deleteCustomer = async (id: string) => {
  try {
    await axios.delete(`/api/customers/${id}`);
    fetchCustomers();
    toast({
      title: 'Success',
      description: 'Customer deleted successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete customer.',
      variant: 'destructive',
    });
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    customer_code: '',
    credit_limit: 0,
    phone_no: '',
    parent: null,
    active: true,
    approved: false,
    default_payment_term: null,
    default_payment_method: null,
    name: '',
    email: '',
    password: '',
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
  fetchCustomers();
  fetchPaymentTerms();
  fetchPaymentMethods();
});
</script>

<style scoped>
/* Add styles for the component */
</style> 