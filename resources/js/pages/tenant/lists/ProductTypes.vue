<template>
  <Head title="Product Types" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Product Type Management</CardTitle>
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
                  v-for="type in availableParentProductTypes"
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
            <Button @click="resetForm" class="w-fit" variant="secondary">Cancel</Button>
            <Button @click="handleSubmit" class="w-fit">{{ isEditing ? 'Update' : 'Create' }} Product Type</Button>
          </div>
        </div>

        <!-- Product Types Table -->
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
              <TableRow v-for="productType in productTypes" :key="productType.id" class="bg-background/50">
                <TableCell>
                  <span :style="{ paddingLeft: `${getIndentationLevel(productType)}rem` }">
                    <span v-if="productType.parent">• </span>{{ productType.type_name }}
                  </span>
                </TableCell>
                <TableCell>
                  <Badge :variant="productType.active ? 'default' : 'secondary'">
                    {{ productType.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="productType.approved ? 'default' : 'secondary'">
                    {{ productType.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ productType.approver ? productType.approver.name : 'N/A' }}</TableCell>
                <TableCell>{{ productType.creator ? productType.creator.name : 'N/A' }}</TableCell>
                <TableCell>{{ productType.updater ? productType.updater.name : 'N/A' }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editProductType(productType)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showConfirmDelete(productType)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="productTypes.length === 0">
                <TableCell colspan="7" class="h-24 text-center">
                  No product types found.
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
          <h3 class="text-lg font-semibold text-foreground">Delete Product Type</h3>
          <button @click="hideDeleteConfirm" class="text-muted-foreground hover:text-foreground transition-colors">
            <X class="h-5 w-5" />
          </button>
        </div>
        <p class="text-muted-foreground mb-6">
          Are you sure you want to delete product type <strong class="text-foreground">{{ productTypeToDelete?.type_name }}</strong>? This action cannot be undone.
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
    title: 'Product Types',
    href: '/product-types',
  },
];

interface ProductType {
  id: string;
  type_name: string;
  active: boolean;
  approved: boolean;
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
  parent?: string;
}

const productTypes: Ref<ProductType[]> = ref([]);
const isEditing = ref(false);
const productTypeToDelete: Ref<ProductType | null> = ref(null);
const showConfirmDeleteModal = ref(false);

const form = ref({
  id: undefined as string | undefined,
  type_name: '',
  active: true,
  approved: true,
  parent: undefined as string | undefined | null,
});

const availableParentProductTypes = computed(() => {
  if (!isEditing.value || !form.value.id) return productTypes.value;
  return productTypes.value.filter(type => type.id !== form.value.id);
});

function hasCircularReference(parentId: string | null | undefined): boolean {
  if (!parentId || !form.value.id) return false;
  let currentParentId = parentId;
  const visited = new Set<string>();
  while (currentParentId) {
    if (visited.has(currentParentId)) return true;
    if (currentParentId === form.value.id) return true;
    visited.add(currentParentId);
    const parentType = productTypes.value.find(t => t.id === currentParentId);
    currentParentId = parentType?.parent || null;
  }
  return false;
}

const sortProductTypesHierarchically = (types: ProductType[]): ProductType[] => {
  const typeMap = new Map<string, ProductType>();
  types.forEach(type => typeMap.set(type.id, type));

  const sortedTypes: ProductType[] = [];

  const addTypeWithChildren = (type: ProductType) => {
    sortedTypes.push(type);
    const children = types.filter(t => t.parent === type.id);
    children.forEach(addTypeWithChildren);
  };

  types.filter(type => !type.parent).forEach(addTypeWithChildren);

  return sortedTypes;
};

const getIndentationLevel = (productType: ProductType): number => {
  let level = 0;
  let currentType = productType;
  while (currentType.parent) {
    level++;
    currentType = productTypes.value.find(t => t.id === currentType.parent) || currentType;
  }
  return level * 1.5;
};

const fetchProductTypes = async () => {
  try {
    const response = await axios.get('/api/product-types');
    productTypes.value = sortProductTypesHierarchically(response.data);
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch product types',
      variant: 'destructive',
    });
  }
};

const handleSubmit = async () => {
  // Prevent circular reference
  if (form.value.parent && hasCircularReference(form.value.parent)) {
    toast({
      title: 'Error',
      description: 'A product type cannot be its own parent or create a circular reference.',
      variant: 'destructive',
    });
    return;
  }
  try {
    if (isEditing.value) {
      await axios.put(`/api/product-types/${form.value.id}`, form.value);
      toast({ title: 'Success', description: 'Product type updated successfully!' });
    } else {
      await axios.post('/api/product-types', form.value);
      toast({ title: 'Success', description: 'Product type created successfully!' });
    }
    await fetchProductTypes();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editProductType = (productType: ProductType) => {
  form.value = {
    id: productType.id,
    type_name: productType.type_name,
    active: productType.active,
    approved: productType.approved,
    parent: productType.parent || null,
  };
  isEditing.value = true;
};

const showConfirmDelete = (productType: ProductType) => {
  productTypeToDelete.value = productType;
  showConfirmDeleteModal.value = true;
};

const hideDeleteConfirm = () => {
  showConfirmDeleteModal.value = false;
  productTypeToDelete.value = null;
};

const confirmDelete = async () => {
  if (!productTypeToDelete.value) return;
  
  try {
    await axios.delete(`/api/product-types/${productTypeToDelete.value.id}`);
    await fetchProductTypes();
    toast({
      title: 'Success',
      description: 'Product type deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete product type',
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
    uiapproved: true,
    parent: null,
  };
  isEditing.value = false;
};

onMounted(() => {
  fetchProductTypes();
});
</script>

<style scoped>
/* Add your styles here */
</style> 