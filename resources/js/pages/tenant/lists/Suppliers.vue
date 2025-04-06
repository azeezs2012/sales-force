<template>
  <Head title="Suppliers" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Supplier Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 space-y-6">
          <!-- Supplier Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">Supplier Details</h3>
            <div class="grid grid-cols-4 gap-4">
              <Input
                v-model="form.supplier_code"
                placeholder="Supplier Code"
                class="bg-background"
                required
              />
              <Input
                v-model="form.phone_no"
                placeholder="Phone Number"
                class="bg-background"
              />
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

          <Button @click="handleSubmit" class="w-fit">
            {{ isEditing ? 'Update' : 'Create' }} Supplier
          </Button>
        </div>

        <!-- Suppliers Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Name</TableHead>
                <TableHead class="uppercase">Email</TableHead>
                <TableHead class="uppercase">Supplier Code</TableHead>
                <TableHead class="uppercase">Phone Number</TableHead>
                <TableHead class="uppercase">Default Payment Method</TableHead>
                <TableHead class="uppercase">Default Payment Term</TableHead>
                <TableHead class="uppercase">Active</TableHead>
                <TableHead class="uppercase">Approved</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="supplier in suppliers" :key="supplier.id" class="bg-background/50">
                <TableCell>{{ supplier.user?.name }}</TableCell>
                <TableCell>{{ supplier.user?.email }}</TableCell>
                <TableCell>{{ supplier.supplier_code }}</TableCell>
                <TableCell>{{ supplier.phone_no }}</TableCell>
                <TableCell>{{ supplier.default_payment_method?.name }}</TableCell>
                <TableCell>{{ supplier.default_payment_term?.name }}</TableCell>
                <TableCell>
                  <Badge :variant="supplier.active ? 'default' : 'secondary'">
                    {{ supplier.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="supplier.approved ? 'default' : 'secondary'">
                    {{ supplier.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editSupplier(supplier)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showDeleteDialog(supplier)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="suppliers.length === 0">
                <TableCell colspan="9" class="h-24 text-center">
                  No suppliers found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="!!supplierToDelete" @update:open="closeDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Supplier</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this supplier? This action cannot be undone.
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
const isEditing = ref(false);
const showPassword = ref(false);
const supplierToDelete: Ref<Supplier | null> = ref(null);

const form = ref({
  id: undefined as string | undefined,
  name: '',
  email: '',
  password: '',
  supplier_code: '',
  phone_no: '',
  default_payment_method: undefined as string | undefined,
  default_payment_term: undefined as string | undefined,
  active: true,
  approved: true,
});

const fetchSuppliers = async () => {
  try {
    const response = await axios.get('/api/suppliers');
    suppliers.value = response.data;
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch suppliers',
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

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/suppliers/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'Supplier updated successfully!',
      });
    } else {
      await axios.post('/api/suppliers', form.value);
      toast({
        title: 'Success',
        description: 'Supplier created successfully!',
      });
    }
    await fetchSuppliers();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editSupplier = (supplier: Supplier) => {
  form.value = {
    id: supplier.id,
    name: supplier.user?.name || '',
    email: supplier.user?.email || '',
    password: '',
    supplier_code: supplier.supplier_code,
    phone_no: supplier.phone_no || '',
    default_payment_method: supplier.default_payment_method?.id,
    default_payment_term: supplier.default_payment_term?.id,
    active: supplier.active,
    approved: supplier.approved,
  };
  isEditing.value = true;
};

const showDeleteDialog = (supplier: Supplier) => {
  supplierToDelete.value = supplier;
};

const closeDeleteDialog = () => {
  supplierToDelete.value = null;
};

const confirmDelete = async () => {
  if (!supplierToDelete.value) return;
  
  try {
    await axios.delete(`/api/suppliers/${supplierToDelete.value.id}`);
    await fetchSuppliers();
    toast({
      title: 'Success',
      description: 'Supplier deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete supplier',
      variant: 'destructive',
    });
  } finally {
    closeDeleteDialog();
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
    default_payment_method: undefined,
    default_payment_term: undefined,
    active: true,
    approved: true,
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
  fetchSuppliers();
  fetchPaymentMethods();
  fetchPaymentTerms();
});
</script>

<style scoped>
/* Add your styles here */
</style> 