<template>
  <Head title="Payment Terms" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Payment Term Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 space-y-6">
          <!-- Payment Term Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">Payment Term Details</h3>
            <div class="grid grid-cols-4 gap-4">
              <Input
                v-model="form.code"
                placeholder="Payment Term Code"
                class="bg-background"
                required
              />
              <Input
                v-model="form.name"
                placeholder="Name"
                class="bg-background"
                required
              />
              <Input
                v-model="form.days"
                type="number"
                placeholder="Days"
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
              <Button @click="handleSubmit" class="w-fit">
                {{ isEditing ? 'Update' : 'Create' }} Payment Term
              </Button>
            </div>
          </div>
        </div>

        <!-- Payment Terms Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Code</TableHead>
                <TableHead class="uppercase">Name</TableHead>
                <TableHead class="uppercase">Days</TableHead>
                <TableHead class="uppercase">Active</TableHead>
                <TableHead class="uppercase">Approved</TableHead>
                <TableHead class="uppercase">Approved By</TableHead>
                <TableHead class="uppercase">Created By</TableHead>
                <TableHead class="uppercase">Updated By</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="paymentTerm in paymentTerms" :key="paymentTerm.id" class="bg-background/50">
                <TableCell>{{ paymentTerm.code }}</TableCell>
                <TableCell>{{ paymentTerm.name }}</TableCell>
                <TableCell>{{ paymentTerm.days }}</TableCell>
                <TableCell>
                  <Badge :variant="paymentTerm.active ? 'default' : 'secondary'">
                    {{ paymentTerm.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="paymentTerm.approved ? 'default' : 'secondary'">
                    {{ paymentTerm.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ paymentTerm.approver ? paymentTerm.approver.name : 'N/A' }}</TableCell>
                <TableCell>{{ paymentTerm.creator ? paymentTerm.creator.name : 'N/A' }}</TableCell>
                <TableCell>{{ paymentTerm.updater ? paymentTerm.updater.name : 'N/A' }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editPaymentTerm(paymentTerm)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showDeleteDialog(paymentTerm)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="paymentTerms.length === 0">
                <TableCell colspan="9" class="h-24 text-center">
                  No payment terms found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="!!paymentTermToDelete" @update:open="closeDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Payment Term</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this payment term? This action cannot be undone.
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
import { Pencil, Trash } from 'lucide-vue-next';
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

const { toast } = useToast();

const breadcrumbs = [
  {
    title: 'Payment Terms',
    href: '/payment-terms',
  },
];

interface PaymentTerm {
  id?: number;
  name?: string;
  code?: string;
  days?: number;
  active?: boolean;
  approved?: boolean;
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
}

const paymentTerms: Ref<PaymentTerm[]> = ref([]);
const isEditing = ref(false);
const paymentTermToDelete: Ref<PaymentTerm | null> = ref(null);

const form = ref({
  id: undefined as number | undefined,
  name: '',
  code: '',
  days: 0,
  active: true,
  approved: true,
});

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
      await axios.put(`/api/payment-terms/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'Payment term updated successfully!',
      });
    } else {
      await axios.post('/api/payment-terms', form.value);
      toast({
        title: 'Success',
        description: 'Payment term created successfully!',
      });
    }
    await fetchPaymentTerms();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editPaymentTerm = (paymentTerm: PaymentTerm) => {
  form.value = {
    id: paymentTerm.id,
    name: paymentTerm.name || '',
    code: paymentTerm.code || '',
    days: paymentTerm.days || 0,
    active: paymentTerm.active || true,
    approved: paymentTerm.approved || true,
  };
  isEditing.value = true;
};

const showDeleteDialog = (paymentTerm: PaymentTerm) => {
  paymentTermToDelete.value = paymentTerm;
};

const closeDeleteDialog = () => {
  paymentTermToDelete.value = null;
};

const confirmDelete = async () => {
  if (!paymentTermToDelete.value) return;
  
  try {
    await axios.delete(`/api/payment-terms/${paymentTermToDelete.value.id}`);
    await fetchPaymentTerms();
    toast({
      title: 'Success',
      description: 'Payment term deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete payment term',
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
    code: '',
    days: 0,
    active: true,
    approved: true,
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