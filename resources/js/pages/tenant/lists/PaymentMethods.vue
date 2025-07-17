<template>
  <Head title="Payment Methods" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Payment Method Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 space-y-6">
          <div class="grid grid-cols-4 gap-4">
            <Input
              v-model="form.method_name"
              placeholder="Method Name"
              class="bg-background"
              required
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
          <!-- Action Buttons -->
          <div class="flex gap-2">
            <Button @click="resetForm" class="w-fit" variant="secondary">Cancel</Button>
            <Button @click="handleSubmit" class="w-fit">{{ isEditing ? 'Update' : 'Create' }} Payment Method</Button>
          </div>
        </div>

        <!-- Payment Methods Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Method Name</TableHead>
                <TableHead class="uppercase">Active</TableHead>
                <TableHead class="uppercase">Approved</TableHead>
                <TableHead class="uppercase">Approved By</TableHead>
                <TableHead class="uppercase">Created By</TableHead>
                <TableHead class="uppercase">Updated By</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="paymentMethod in paymentMethods" :key="paymentMethod.id" class="bg-background/50">
                <TableCell>{{ paymentMethod.method_name }}</TableCell>
                <TableCell>
                  <Badge :variant="paymentMethod.active ? 'default' : 'secondary'">
                    {{ paymentMethod.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="paymentMethod.approved ? 'default' : 'secondary'">
                    {{ paymentMethod.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ paymentMethod.approver ? paymentMethod.approver.name : 'N/A' }}</TableCell>
                <TableCell>{{ paymentMethod.creator ? paymentMethod.creator.name : 'N/A' }}</TableCell>
                <TableCell>{{ paymentMethod.updater ? paymentMethod.updater.name : 'N/A' }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editPaymentMethod(paymentMethod)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showConfirmDelete(paymentMethod)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="paymentMethods.length === 0">
                <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                  No payment methods found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Custom Delete Confirmation Modal -->
    <div v-if="showConfirmDeleteModal" class="fixed inset-0 flex items-center justify-center bg-background/80 backdrop-blur-sm">
      <div class="bg-background border border-border rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-foreground">Delete Payment Method</h3>
          <button @click="hideDeleteConfirm" class="text-muted-foreground hover:text-foreground transition-colors">
            <X class="h-5 w-5" />
          </button>
        </div>
        <p class="text-muted-foreground mb-6">
          Are you sure you want to delete payment method <strong class="text-foreground">{{ paymentMethodToDelete?.method_name }}</strong>? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
          <Button variant="outline" @click="hideDeleteConfirm">Cancel</Button>
          <Button variant="destructive" @click="confirmDelete">Delete</Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/TenantAppLayout.vue';
import { ref, onMounted } from 'vue';
import type { Ref } from 'vue';
import axios from 'axios';
import { useToast } from '@/components/ui/toast/use-toast';
import { Pencil, Trash, X } from 'lucide-vue-next';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
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

const { toast } = useToast();

const breadcrumbs = [
  {
    title: 'Payment Methods',
    href: '/payment-methods',
  },
];

interface PaymentMethod {
  id: string;
  method_name: string;
  active: boolean;
  approved: boolean;
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
}

const paymentMethods: Ref<PaymentMethod[]> = ref([]);
const isEditing = ref(false);
const paymentMethodToDelete: Ref<PaymentMethod | null> = ref(null);
const showConfirmDeleteModal = ref(false);

const form = ref({
  id: undefined as string | undefined,
  method_name: '',
  active: true,
  approved: true,
});

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

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/payment-methods/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'Payment method updated successfully!',
      });
    } else {
    await axios.post('/api/payment-methods', form.value);
    toast({
      title: 'Success',
      description: 'Payment method created successfully!',
      });
    }
    await fetchPaymentMethods();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editPaymentMethod = (paymentMethod: PaymentMethod) => {
  form.value = {
    id: paymentMethod.id,
    method_name: paymentMethod.method_name,
    active: paymentMethod.active,
    approved: paymentMethod.approved,
  };
  isEditing.value = true;
};

const showConfirmDelete = (paymentMethod: PaymentMethod) => {
  paymentMethodToDelete.value = paymentMethod;
  showConfirmDeleteModal.value = true;
};

const hideDeleteConfirm = () => {
  showConfirmDeleteModal.value = false;
  paymentMethodToDelete.value = null;
};

const confirmDelete = async () => {
  if (!paymentMethodToDelete.value) return;
  
  try {
    await axios.delete(`/api/payment-methods/${paymentMethodToDelete.value.id}`);
    await fetchPaymentMethods();
    toast({
      title: 'Success',
      description: 'Payment method deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete payment method',
      variant: 'destructive',
    });
  } finally {
    hideDeleteConfirm();
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    method_name: '',
    active: true,
    approved: true,
  };
  isEditing.value = false;
};

onMounted(() => {
  fetchPaymentMethods();
});
</script>

<style scoped>
/* Add your styles here */
</style> 