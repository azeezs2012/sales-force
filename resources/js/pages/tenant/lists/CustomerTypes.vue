<template>
  <Head title="Customer Types" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Customer Type Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 space-y-6">
          <div class="grid grid-cols-4 gap-4">
            <Input
              v-model="form.type_name"
              placeholder="Type Name"
              class="bg-background"
              required
            />
            <Select v-model="form.parent">
              <SelectTrigger class="bg-background">
                <SelectValue placeholder="Select Parent Type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">None</SelectItem>
                <SelectItem
                  v-for="type in availableParentCustomerTypes"
                  :key="type.id"
                  :value="type.id"
                >
                  {{ type.type_name }}
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
            <Button @click="resetForm" class="w-fit" variant="secondary">
              Cancel
            </Button>
            <Button @click="handleSubmit" class="w-fit">
              {{ isEditing ? 'Update' : 'Create' }} Customer Type
            </Button>
          </div>
        </div>

        <!-- Customer Types Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Type Name</TableHead>
                <TableHead class="uppercase">Active</TableHead>
                <TableHead class="uppercase">Approved</TableHead>
                <TableHead class="uppercase">Approved By</TableHead>
                <TableHead class="uppercase">Created By</TableHead>
                <TableHead class="uppercase">Updated By</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="customerType in customerTypes" :key="customerType.id" class="bg-background/50">
                <TableCell>
                  <span :style="{ paddingLeft: `${getIndentationLevel(customerType)}rem` }">
                    <span v-if="customerType.parent">• </span>{{ customerType.type_name }}
                  </span>
                </TableCell>
                <TableCell>
                  <Badge :variant="customerType.active ? 'default' : 'secondary'">
                    {{ customerType.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="customerType.approved ? 'default' : 'secondary'">
                    {{ customerType.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ customerType.approver ? customerType.approver.name : 'N/A' }}</TableCell>
                <TableCell>{{ customerType.creator ? customerType.creator.name : 'N/A' }}</TableCell>
                <TableCell>{{ customerType.updater ? customerType.updater.name : 'N/A' }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editCustomerType(customerType)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showConfirmDelete(customerType)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="customerTypes.length === 0">
                <TableCell colspan="7" class="h-24 text-center">
                  No customer types found.
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
          <h3 class="text-lg font-semibold text-foreground">Delete Customer Type</h3>
          <button @click="hideDeleteConfirm" class="text-muted-foreground hover:text-foreground transition-colors">
            <X class="h-5 w-5" />
          </button>
        </div>
        <p class="text-muted-foreground mb-6">
          Are you sure you want to delete customer type <strong class="text-foreground">{{ customerTypeToDelete?.type_name }}</strong>? This action cannot be undone.
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
import { ref, onMounted, computed } from 'vue';
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
    title: 'Customer Types',
    href: '/customer-types',
  },
];

interface CustomerType {
  id: string;
  type_name: string;
  active: boolean;
  approved: boolean;
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
  parent?: string;
}

const customerTypes: Ref<CustomerType[]> = ref([]);
const isEditing = ref(false);
const customerTypeToDelete: Ref<CustomerType | null> = ref(null);
const showConfirmDeleteModal = ref(false);

const form = ref({
  id: undefined as string | undefined,
  type_name: '',
  active: true,
  approved: true,
  parent: null as string | null,
});

// Computed property to filter out current customer type from parent options
const availableParentCustomerTypes = computed(() => {
  if (!isEditing.value || !form.value.id) {
    return customerTypes.value;
  }
  return customerTypes.value.filter(type => type.id !== form.value.id);
});

// Function to check for circular references
const hasCircularReference = (parentId: string | null | undefined): boolean => {
  if (!parentId || !form.value.id) return false;
  
  let currentParentId = parentId;
  const visited = new Set<string>();
  
  while (currentParentId) {
    if (visited.has(currentParentId)) return true;
    if (currentParentId === form.value.id) return true;
    
    visited.add(currentParentId);
    const parentType = customerTypes.value.find(t => t.id === currentParentId);
    currentParentId = parentType?.parent;
  }
  
  return false;
};

const sortCustomerTypesHierarchically = (types: CustomerType[]): CustomerType[] => {
  const typeMap = new Map<string, CustomerType>();
  types.forEach(type => typeMap.set(type.id, type));

  const sortedTypes: CustomerType[] = [];

  const addTypeWithChildren = (type: CustomerType) => {
    sortedTypes.push(type);
    const children = types.filter(t => t.parent === type.id);
    children.forEach(addTypeWithChildren);
  };

  types.filter(type => !type.parent).forEach(addTypeWithChildren);

  return sortedTypes;
};

const getIndentationLevel = (customerType: CustomerType): number => {
  let level = 0;
  let currentType = customerType;
  while (currentType.parent) {
    level++;
    currentType = customerTypes.value.find(t => t.id === currentType.parent) || currentType;
  }
  return level * 1.5;
};

const fetchCustomerTypes = async () => {
  try {
    const response = await axios.get('/api/customer-types');
    customerTypes.value = sortCustomerTypesHierarchically(response.data);
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch customer types',
      variant: 'destructive',
    });
  }
};

const handleSubmit = async () => {
  // Prevent circular reference
  if (form.value.parent && hasCircularReference(form.value.parent)) {
    toast({
      title: 'Error',
      description: 'A customer type cannot be its own parent or create a circular reference.',
      variant: 'destructive',
    });
    return;
  }
  
  try {
    if (isEditing.value) {
      await axios.put(`/api/customer-types/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'Customer type updated successfully!',
      });
    } else {
      await axios.post('/api/customer-types', form.value);
      toast({
        title: 'Success',
        description: 'Customer type created successfully!',
      });
    }
    await fetchCustomerTypes();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editCustomerType = (customerType: CustomerType) => {
  form.value = {
    id: customerType.id,
    type_name: customerType.type_name,
    active: customerType.active,
    approved: customerType.approved,
    parent: customerType.parent || null,
  };
  isEditing.value = true;
};

const showConfirmDelete = (customerType: CustomerType) => {
  customerTypeToDelete.value = customerType;
  showConfirmDeleteModal.value = true;
};

const hideDeleteConfirm = () => {
  showConfirmDeleteModal.value = false;
  customerTypeToDelete.value = null;
};

const confirmDelete = async () => {
  if (!customerTypeToDelete.value) return;
  
  try {
    await axios.delete(`/api/customer-types/${customerTypeToDelete.value.id}`);
    await fetchCustomerTypes();
    toast({
      title: 'Success',
      description: 'Customer type deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete customer type',
      variant: 'destructive',
    });
  } finally {
    hideDeleteConfirm();
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    type_name: '',
    active: true,
    approved: true,
    parent: null,
  };
  isEditing.value = false;
};

onMounted(() => {
  fetchCustomerTypes();
});
</script>

<style scoped>
/* Add your styles here */
</style> 