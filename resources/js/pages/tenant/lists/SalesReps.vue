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
          <div class="grid grid-cols-4 gap-4">
            <Input v-model="form.code" placeholder="Sales Rep Code" class="bg-background" required />
            <Input v-model="form.name" placeholder="Name" class="bg-background" required />
            <Input v-model="form.email" type="email" placeholder="Email" class="bg-background" required />
            <!-- Password field with show/hide toggle -->
            <div class="relative">
              <Input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Password"
                class="bg-background pr-10"
                :required="!isEditing"
              />
              <button
                type="button"
                @click="togglePasswordVisibility"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
              >
                <Eye v-if="!showPassword" class="h-4 w-4" />
                <EyeOff v-else class="h-4 w-4" />
              </button>
            </div>
          </div>
          <div class="grid grid-cols-4 gap-4">
            <Select v-model="form.parent">
              <SelectTrigger class="bg-background">
                <SelectValue placeholder="Select Parent Sales Rep" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">None</SelectItem>
                <SelectItem v-for="salesRep in availableParentSalesReps" :key="salesRep.id" :value="salesRep.id">
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
          </div>
          <!-- Action Buttons -->
          <div class="flex gap-2">
            <Button @click="resetForm" class="w-fit" variant="secondary">Cancel</Button>
            <Button @click="handleSubmit" class="w-fit">{{ isEditing ? 'Update' : 'Create' }} Sales Rep</Button>
          </div>
        </div>
        
        <!-- Sales Reps Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Code</TableHead>
                <TableHead class="uppercase">Name</TableHead>
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
                <TableCell>{{ salesRep.user?.name || salesRep.name }}</TableCell>
                <TableCell>{{ salesRep.user?.email || salesRep.email }}</TableCell>
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
                      <DropdownMenuItem @click="showConfirmDelete(salesRep)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="salesReps.length === 0">
                <TableCell colspan="9" class="h-24 text-center text-muted-foreground">
                  No sales representatives found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Custom Delete Confirmation Modal -->
    <div v-if="showConfirmDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm">
      <div class="bg-background border border-border rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-foreground">Delete Sales Representative</h3>
          <button @click="hideDeleteConfirm" class="text-muted-foreground hover:text-foreground transition-colors">
            <X class="h-5 w-5" />
          </button>
        </div>
        <p class="text-muted-foreground mb-6">
          Are you sure you want to delete sales representative <strong class="text-foreground">{{ salesRepToDelete?.name || salesRepToDelete?.user?.name }}</strong>? This action cannot be undone.
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useToast } from '@/components/ui/toast/use-toast';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import type { Ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/ui/button/Button.vue';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Pencil, Trash, X, Eye, EyeOff } from 'lucide-vue-next';

const { toast } = useToast()

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
const form: Ref<Partial<SalesRep>> = ref({
  id: undefined,
  name: '',
  code: '',
  email: '',
  password: '',
  active: true,
  approved: true,
  parent: undefined,
});

const { appearance, updateAppearance } = useAppearance();

const showPassword = ref(false);

// Computed property to filter out current sales rep from parent options
const availableParentSalesReps = computed(() => {
  if (!isEditing.value || !form.value.id) {
    return salesReps.value;
  }
  return salesReps.value.filter(salesRep => salesRep.id !== form.value.id);
});

// Function to check for circular references
const hasCircularReference = (parentId: number | null | undefined): boolean => {
  if (!parentId || !form.value.id) return false;
  
  let currentParentId = parentId;
  const visited = new Set<number>();
  
  while (currentParentId) {
    if (visited.has(currentParentId)) return true;
    if (currentParentId === form.value.id) return true;
    
    visited.add(currentParentId);
    const parentSalesRep = salesReps.value.find(s => s.id === currentParentId);
    currentParentId = parentSalesRep?.parent || null;
  }
  
  return false;
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

const sortSalesRepsHierarchically = (salesReps: SalesRep[]): SalesRep[] => {
  const salesRepMap = new Map<string, SalesRep>();
  salesReps.forEach(salesRep => salesRepMap.set(salesRep.id?.toString() || '', salesRep));

  const sortedSalesReps: SalesRep[] = [];

  const addSalesRepWithChildren = (salesRep: SalesRep) => {
    sortedSalesReps.push(salesRep);
    const children = salesReps.filter(s => s.parent === salesRep.id);
    children.forEach(addSalesRepWithChildren);
  };

  salesReps.filter(salesRep => !salesRep.parent).forEach(addSalesRepWithChildren);

  return sortedSalesReps;
};

const handleSubmit = async () => {
  // Prevent circular reference
  if (form.value.parent && hasCircularReference(form.value.parent)) {
    toast({
      title: 'Error',
      description: 'A sales rep cannot be its own parent or create a circular reference.',
      variant: 'destructive',
    });
    return;
  }

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
    name: salesRep.user?.name || salesRep.name || '',
    code: salesRep.code || '',
    email: salesRep.user?.email || salesRep.email || '',
    password: '',
    active: salesRep.active,
    approved: salesRep.approved,
    parent: salesRep.parent,
  };
  isEditing.value = true;
};

const showConfirmDeleteModal = ref(false);

const showConfirmDelete = (salesRep: SalesRep) => {
  salesRepToDelete.value = salesRep;
  showConfirmDeleteModal.value = true;
};

const hideDeleteConfirm = () => {
  showConfirmDeleteModal.value = false;
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
    hideDeleteConfirm();
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
    parent: undefined,
  };
  isEditing.value = false;
};

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const generateStrongPassword = () => {
  const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=";
  let password = "";
  for (let i = 0, n = charset.length; i < 12; ++i) {
    password += charset.charAt(Math.floor(Math.random() * n));
  }
  form.value.password = password;
};

onMounted(() => {
  fetchSalesReps();
});

// Function to calculate indentation level based on hierarchy
const getIndentationLevel = (salesRep: SalesRep): number => {
  let level = 0;
  let currentSalesRep = salesRep;
  while (currentSalesRep.parent) {
    level++;
    currentSalesRep = salesReps.value.find(s => s.id === currentSalesRep.parent) || currentSalesRep;
  }
  return level * 1.5; // Adjust multiplier for desired indentation
};


</script>

<style scoped>
.relative {
  position: relative;
}
.absolute {
  position: absolute;
}

/* Override browser autofill styling */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
  -webkit-box-shadow: 0 01000l(var(--background)) inset !important;
  -webkit-text-fill-color: hsl(var(--foreground)) !important;
  transition: background-color 50s ease-in-out 0s;
}

/* For Firefox */
input:-moz-autofill {
  background-color: hsl(var(--background)) !important;
  color: hsl(var(--foreground)) !important;
}
</style> 