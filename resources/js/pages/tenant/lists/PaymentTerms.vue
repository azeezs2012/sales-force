<template>
  <Head title="Payment Terms" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl font-bold mb-4">Payment Term Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 space-y-6">
          <div class="grid grid-cols-4 gap-4">
            <Input
              v-model="form.payment_term_name"
              placeholder="Term Name"
              class="bg-background"
              required
            />
            <Input
              v-model="form.duration_count"
              type="number"
              placeholder="Duration Count"
              class="bg-background"
              required
            />
            <Select v-model="form.duration_unit">
              <SelectTrigger class="bg-background">
                <SelectValue placeholder="Select Duration Unit" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="days">Days</SelectItem>
                <SelectItem value="months">Months</SelectItem>
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
          <!-- Action Buttons -->
          <div class="flex gap-2">
            <Button @click="resetForm" class="w-fit" variant="secondary">Cancel</Button>
            <Button @click="handleSubmit" class="w-fit">{{ isEditing ? 'Update' : 'Create' }} Payment Term</Button>
          </div>
        </div>

        <!-- Payment Terms Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Term Name</TableHead>
                <TableHead class="uppercase">Duration</TableHead>
                <TableHead class="uppercase">Active</TableHead>
                <TableHead class="uppercase">Approved</TableHead>
                <TableHead class="uppercase">Approved By</TableHead>
                <TableHead class="uppercase">Created By</TableHead>
                <TableHead class="uppercase">Updated By</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="term in paymentTerms" :key="term.id" class="bg-background/50">
                <TableCell>{{ term.payment_term_name }}</TableCell>
                <TableCell>{{ term.duration_count }} {{ term.duration_unit }}</TableCell>
                <TableCell>
                  <Badge :variant="term.active ? 'default' : 'secondary'">
                    {{ term.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="term.approved ? 'default' : 'secondary'">
                    {{ term.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ term.approver ? term.approver.name : 'N/A' }}</TableCell>
                <TableCell>{{ term.creator ? term.creator.name : 'N/A' }}</TableCell>
                <TableCell>{{ term.updater ? term.updater.name : 'N/A' }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editPaymentTerm(term)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showConfirmDelete(term)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="paymentTerms.length === 0">
                <TableCell colspan="8" class="h-24 text-center text-muted-foreground">
                  No payment terms found.
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
          <h3 class="text-lg font-semibold text-foreground">Delete Payment Term</h3>
          <button @click="hideDeleteConfirm" class="text-muted-foreground hover:text-foreground transition-colors">
            <X class="h-5" />
          </button>
        </div>
        <p class="text-muted-foreground mb-6">
          Are you sure you want to delete payment term <strong class="text-foreground">{{ paymentTermToDelete?.payment_term_name }}</strong>? This action cannot be undone.
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
const isEditing = ref(false);
const paymentTermToDelete: Ref<PaymentTerm | null> = ref(null);
const showConfirmDeleteModal = ref(false);

const form = ref({
  id: undefined as string | undefined,
  payment_term_name: '',
  duration_count: 0,
  duration_unit: 'days',
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

const editPaymentTerm = (term: PaymentTerm) => {
  form.value = {
    id: term.id,
    payment_term_name: term.payment_term_name,
    duration_count: term.duration_count,
    duration_unit: term.duration_unit,
    active: term.active,
    approved: term.approved,
  };
  isEditing.value = true;
};

const showConfirmDelete = (term: PaymentTerm) => {
  paymentTermToDelete.value = term;
  showConfirmDeleteModal.value = true;
};

const hideDeleteConfirm = () => {
  showConfirmDeleteModal.value = false;
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
    hideDeleteConfirm();
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    payment_term_name: '',
    duration_count: 0,
    duration_unit: 'days',
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