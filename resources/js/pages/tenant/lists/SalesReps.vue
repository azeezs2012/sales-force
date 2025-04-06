<template>
  <Head title="Sales Representatives" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Sales Representative Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 space-y-6">
          <!-- Sales Rep Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">Sales Representative Details</h3>
            <div class="grid grid-cols-4 gap-4">
              <Input
                v-model="form.code"
                placeholder="Sales Rep Code"
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
            </div>
          </div>

          <!-- Additional Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">Additional Details</h3>
            <div class="grid grid-cols-4 gap-4">
              <Select v-model="form.parent">
                <SelectTrigger class="bg-background">
                  <SelectValue placeholder="Select Parent Sales Rep" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="null">None</SelectItem>
                  <SelectItem
                    v-for="salesRep in salesReps"
                    :key="salesRep.id"
                    :value="salesRep.id"
                  >
                    {{ salesRep.code }}
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
              <Button type="button" @click="generateStrongPassword">
                Generate Password
              </Button>
              <Button @click="handleSubmit" class="w-fit">
                {{ isEditing ? 'Update' : 'Create' }} Sales Rep
              </Button>
            </div>
          </div>
        </div>

        <!-- Sales Reps Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Code</TableHead>
                <TableHead class="uppercase">Email</TableHead>
                <TableHead class="uppercase">Active</TableHead>
                <TableHead class="uppercase">Approved</TableHead>
                <TableHead class="uppercase">Approved By</TableHead>
                <TableHead class="uppercase">Created By</TableHead>
                <TableHead class="uppercase">Updated By</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="salesRep in salesReps" :key="salesRep.id" class="bg-background/50">
                <TableCell>
                  <span :style="{ paddingLeft: `${getIndentationLevel(salesRep)}rem` }">
                    <span v-if="salesRep.parent">• </span>{{ salesRep.code }}
                  </span>
                </TableCell>
                <TableCell>{{ salesRep.user?.email }}</TableCell>
                <TableCell>
                  <Badge :variant="salesRep.active ? 'default' : 'secondary'">
                    {{ salesRep.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="salesRep.approved ? 'default' : 'secondary'">
                    {{ salesRep.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ salesRep.approver ? salesRep.approver.name : 'N/A' }}</TableCell>
                <TableCell>{{ salesRep.creator ? salesRep.creator.name : 'N/A' }}</TableCell>
                <TableCell>{{ salesRep.updater ? salesRep.updater.name : 'N/A' }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editSalesRep(salesRep)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showDeleteDialog(salesRep)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="salesReps.length === 0">
                <TableCell colspan="8" class="h-24 text-center">
                  No sales representatives found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="!!salesRepToDelete" @update:open="closeDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Sales Representative</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this sales representative? This action cannot be undone.
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
    title: 'Sales Representatives',
    href: '/sales-reps',
  },
];

interface SalesRep {
  id?: number;
  name?: string;
  code?: string;
  email?: string;
  password?: string;
  active?: boolean;
  approved?: boolean;
  parent?: number | null;
  childSalesReps?: SalesRep[];
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
  user?: { name: string; email: string };
}

const salesReps: Ref<SalesRep[]> = ref([]);
const isEditing = ref(false);
const salesRepToDelete: Ref<SalesRep | null> = ref(null);
const showPassword = ref(false);

const form = ref({
  id: undefined as number | undefined,
  name: '',
  code: '',
  email: '',
  password: '',
  active: true,
  approved: true,
  parent: null as number | null,
});

const getIndentationLevel = (salesRep: SalesRep): number => {
  let level = 0;
  let currentSalesRep = salesRep;
  while (currentSalesRep.parent) {
    level++;
    currentSalesRep = salesReps.value.find(s => s.id === currentSalesRep.parent) || currentSalesRep;
  }
  return level * 1.5;
};

const sortSalesRepsHierarchically = (salesRepList: SalesRep[]): SalesRep[] => {
  const salesRepMap = new Map<string, SalesRep>();
  salesRepList.forEach(salesRep => salesRepMap.set(salesRep.id?.toString() || '', salesRep));

  const sortedSalesReps: SalesRep[] = [];

  const addSalesRepWithChildren = (salesRep: SalesRep) => {
    sortedSalesReps.push(salesRep);
    const children = salesRepList.filter(s => s.parent === salesRep.id);
    children.forEach(addSalesRepWithChildren);
  };

  salesRepList.filter(salesRep => !salesRep.parent).forEach(addSalesRepWithChildren);

  return sortedSalesReps;
};

const fetchSalesReps = async () => {
  try {
    const response = await axios.get('/api/sales-reps');
    salesReps.value = sortSalesRepsHierarchically(response.data);
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
      await axios.put(`/api/sales-reps/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'Sales representative updated successfully!',
      });
    } else {
      await axios.post('/api/sales-reps', form.value);
      toast({
        title: 'Success',
        description: 'Sales representative created successfully!',
      });
    }
    await fetchSalesReps();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editSalesRep = (salesRep: SalesRep) => {
  form.value = {
    id: salesRep.id,
    name: salesRep.user?.name || '',
    code: salesRep.code || '',
    email: salesRep.user?.email || '',
    password: '',
    active: salesRep.active || true,
    approved: salesRep.approved || true,
    parent: salesRep.parent || null,
  };
  isEditing.value = true;
};

const showDeleteDialog = (salesRep: SalesRep) => {
  salesRepToDelete.value = salesRep;
};

const closeDeleteDialog = () => {
  salesRepToDelete.value = null;
};

const confirmDelete = async () => {
  if (!salesRepToDelete.value) return;
  
  try {
    await axios.delete(`/api/sales-reps/${salesRepToDelete.value.id}`);
    await fetchSalesReps();
    toast({
      title: 'Success',
      description: 'Sales representative deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete sales representative',
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
    email: '',
    password: '',
    active: true,
    approved: true,
    parent: null,
  };
  isEditing.value = false;
};

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const generateStrongPassword = () => {
  const length = 12;
  const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
  let password = "";
  for (let i = 0; i < length; i++) {
    const randomIndex = Math.floor(Math.random() * charset.length);
    password += charset[randomIndex];
  }
  form.value.password = password;
};

onMounted(() => {
  fetchSalesReps();
});
</script>

<style scoped>
.relative {
  position: relative;
}
.absolute {
  position: absolute;
}
</style> 