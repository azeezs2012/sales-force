<template>
  <Head title="Customers" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Customer Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 space-y-6">
          <!-- Customer Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">Customer Details</h3>
            <div class="grid grid-cols-4 gap-4">
              <Input
                v-model="form.customer_code"
                placeholder="Customer Code"
                class="bg-background"
                required
              />
              <Input
                v-model="form.credit_limit"
                type="number"
                placeholder="Credit Limit"
                class="bg-background"
              />
              <Input
                v-model="form.phone_no"
                placeholder="Phone Number"
                class="bg-background"
              />
              <Select v-model="form.parent">
                <SelectTrigger class="bg-background">
                  <SelectValue placeholder="Select Parent Customer" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="null">None</SelectItem>
                  <SelectItem
                    v-for="customer in customers"
                    :key="customer.id"
                    :value="customer.id"
                  >
                    {{ customer.customer_code }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <div class="flex items-center gap-4">
                <Label class="flex items-center gap-2">
                  <Switch v-model="form.active" />
                  Active
                </Label>
                <Label class="flex items-center gap-2">
                  <Switch v-model="form.approved" />
                  Approved
                </Label>
              </div>
            </div>
          </div>

          <!-- User Account Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">User Account Details</h3>
            <div class="grid grid-cols-4 gap-4">
              <Input
                v-model="form.name"
                placeholder="Name"
                class="bg-background"
                required
              />
              <Input
                v-model="form.email"
                placeholder="Email"
                type="email"
                class="bg-background"
                required
              />
              <div class="relative">
                <Input
                  :type="showPassword ? 'text' : 'password'"
                  v-model="form.password"
                  placeholder="Password"
                  class="bg-background pr-10"
                />
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  @click="togglePasswordVisibility"
                  class="absolute right-2 top-1/2 -translate-y-1/2"
                >
                  <Eye v-if="!showPassword" class="h-4 w-4" />
                  <EyeOff v-else class="h-4 w-4" />
                </Button>
              </div>
              <Button type="button" @click="generateStrongPassword">
                Generate Password
              </Button>
            </div>
          </div>

          <!-- Payment Term Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">Payment Term</h3>
            <Select v-model="form.default_payment_term">
              <SelectTrigger class="bg-background w-[200px]">
                <SelectValue placeholder="Select Payment Term" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">None</SelectItem>
                <SelectItem
                  v-for="term in paymentTerms"
                  :key="term.id"
                  :value="term.id"
                >
                  {{ term.payment_term_name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Payment Method Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">Payment Method</h3>
            <Select v-model="form.default_payment_method">
              <SelectTrigger class="bg-background w-[200px]">
                <SelectValue placeholder="Select Payment Method" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">None</SelectItem>
                <SelectItem
                  v-for="method in paymentMethods"
                  :key="method.id"
                  :value="method.id"
                >
                  {{ method.method_name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Sales Rep Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">Sales Representative</h3>
            <Select v-model="form.default_sales_rep">
              <SelectTrigger class="bg-background w-[200px]">
                <SelectValue placeholder="Select Sales Rep" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">None</SelectItem>
                <SelectItem
                  v-for="rep in salesReps"
                  :key="rep.id"
                  :value="rep.id"
                >
                  {{ rep.code }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <Button @click="handleSubmit" class="w-fit">
            {{ isEditing ? 'Update' : 'Create' }} Customer
          </Button>
        </div>

        <!-- Customers Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Customer Code</TableHead>
                <TableHead class="uppercase">Credit Limit</TableHead>
                <TableHead class="uppercase">Phone Number</TableHead>
                <TableHead class="uppercase">Active</TableHead>
                <TableHead class="uppercase">Approved</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="customer in customers" :key="customer.id" class="bg-background/50">
                <TableCell>
                  <span :style="{ paddingLeft: `${getIndentationLevel(customer)}rem` }">
                    <span v-if="customer.parent">• </span>{{ customer.customer_code }}
                  </span>
                </TableCell>
                <TableCell>{{ customer.credit_limit }}</TableCell>
                <TableCell>{{ customer.phone_no }}</TableCell>
                <TableCell>
                  <Badge :variant="customer.active ? 'default' : 'secondary'">
                    {{ customer.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="customer.approved ? 'default' : 'secondary'">
                    {{ customer.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editCustomer(customer)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showDeleteDialog(customer)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="customers.length === 0">
                <TableCell colspan="6" class="h-24 text-center">
                  No customers found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="!!customerToDelete" @update:open="closeDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Customer</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this customer? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="ghost" @click="closeDeleteDialog">Cancel</Button>
          <Button variant="destructive" @click="confirmDelete">Delete</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/TenantAppLayout.vue';
import { ref, onMounted } from 'vue';
import type { Ref } from 'vue';
import axios from 'axios';
import { useToast } from '@/components/ui/toast/use-toast';
import { Eye, EyeOff, Pencil, Trash } from 'lucide-vue-next';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const { toast } = useToast();

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
  default_sales_rep?: number | null;
  user?: { name: string; email: string };
  password?: string;
}

const customers: Ref<Customer[]> = ref([]);
const paymentTerms: Ref<any[]> = ref([]);
const paymentMethods: Ref<any[]> = ref([]);
const salesReps: Ref<any[]> = ref([]);
const isEditing = ref(false);
const showPassword = ref(false);
const customerToDelete: Ref<Customer | null> = ref(null);

const form = ref({
  id: undefined as string | undefined,
  customer_code: '',
  credit_limit: 0,
  phone_no: '',
  parent: null as string | null,
  active: true,
  approved: false,
  default_payment_term: null as number | null,
  default_payment_method: null as number | null,
  default_sales_rep: null as number | null,
  name: '',
  email: '',
  password: '',
});

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
  return level * 1.5;
};

const fetchCustomers = async () => {
  try {
    const response = await axios.get('/api/customers');
    customers.value = sortCustomersHierarchically(response.data);
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch customers',
      variant: 'destructive',
    });
  }
};

const fetchPaymentTerms = async () => {
  try {
    const response = await axios.get('/api/payment-terms');
    paymentTerms.value = response.data;
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch payment terms',
      variant: 'destructive',
    });
  }
};

const fetchPaymentMethods = async () => {
  try {
    const response = await axios.get('/api/payment-methods');
    paymentMethods.value = response.data;
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch payment methods',
      variant: 'destructive',
    });
  }
};

const fetchSalesReps = async () => {
  try {
    const response = await axios.get('/api/sales-reps');
    salesReps.value = response.data;
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch sales representatives',
      variant: 'destructive',
    });
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/customers/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'Customer updated successfully!',
      });
    } else {
      await axios.post('/api/customers', form.value);
      toast({
        title: 'Success',
        description: 'Customer created successfully!',
      });
    }
    await fetchCustomers();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editCustomer = (customer: Customer) => {
  form.value = {
    id: customer.id,
    customer_code: customer.customer_code,
    credit_limit: customer.credit_limit,
    phone_no: customer.phone_no,
    parent: customer.parent?.toString() || null,
    active: customer.active,
    approved: customer.approved,
    default_payment_term: customer.default_payment_term,
    default_payment_method: customer.default_payment_method,
    default_sales_rep: customer.default_sales_rep,
    name: customer.user?.name || '',
    email: customer.user?.email || '',
    password: '',
  };
  isEditing.value = true;
};

const showDeleteDialog = (customer: Customer) => {
  customerToDelete.value = customer;
};

const closeDeleteDialog = () => {
  customerToDelete.value = null;
};

const confirmDelete = async () => {
  if (!customerToDelete.value) return;
  
  try {
    await axios.delete(`/api/customers/${customerToDelete.value.id}`);
    await fetchCustomers();
    toast({
      title: 'Success',
      description: 'Customer deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete customer',
      variant: 'destructive',
    });
  } finally {
    closeDeleteDialog();
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
    default_sales_rep: null,
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
  const length = 12;
  const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
  let password = '';
  for (let i = 0; i < length; i++) {
    password += charset.charAt(Math.floor(Math.random() * charset.length));
  }
  form.value.password = password;
};

onMounted(() => {
  fetchCustomers();
  fetchPaymentTerms();
  fetchPaymentMethods();
  fetchSalesReps();
});
</script>

<style scoped>
/* Add styles for the component */
</style> 