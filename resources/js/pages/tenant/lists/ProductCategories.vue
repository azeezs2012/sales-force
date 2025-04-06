<template>
  <Head title="Product Categories" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="flex h-full flex-1 flex-col bg-muted/10">
      <CardHeader>
        <CardTitle class="text-2xl">Product Category Management</CardTitle>
      </CardHeader>
      <CardContent>
        <!-- Create/Edit Form -->
        <div class="mb-6 space-y-6">
          <div class="grid grid-cols-4 gap-4">
            <Input
              v-model="form.category_name"
              placeholder="Category Name"
              class="bg-background"
              required
            />
            <Select v-model="form.parent">
              <SelectTrigger class="bg-background">
                <SelectValue placeholder="Select Parent Category" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">None</SelectItem>
                <SelectItem
                  v-for="category in productCategories"
                  :key="category.id"
                  :value="category.id"
                >
                  {{ category.category_name }}
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
            <Button @click="handleSubmit" class="w-fit">
              {{ isEditing ? 'Update' : 'Create' }} Product Category
            </Button>
          </div>
        </div>

        <!-- Product Categories Table -->
        <div class="rounded-md border bg-card">
          <Table>
            <TableHeader class="bg-muted/50">
              <TableRow>
                <TableHead class="uppercase">Category Name</TableHead>
                <TableHead class="uppercase">Active</TableHead>
                <TableHead class="uppercase">Approved</TableHead>
                <TableHead class="uppercase">Approved By</TableHead>
                <TableHead class="uppercase">Created By</TableHead>
                <TableHead class="uppercase">Updated By</TableHead>
                <TableHead class="w-[100px] uppercase">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="productCategory in productCategories" :key="productCategory.id" class="bg-background/50">
                <TableCell>
                  <span :style="{ paddingLeft: `${getIndentationLevel(productCategory)}rem` }">
                    <span v-if="productCategory.parent">• </span>{{ productCategory.category_name }}
                  </span>
                </TableCell>
                <TableCell>
                  <Badge :variant="productCategory.active ? 'default' : 'secondary'">
                    {{ productCategory.active ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge :variant="productCategory.approved ? 'default' : 'secondary'">
                    {{ productCategory.approved ? 'Yes' : 'No' }}
                  </Badge>
                </TableCell>
                <TableCell>{{ productCategory.approver ? productCategory.approver.name : 'N/A' }}</TableCell>
                <TableCell>{{ productCategory.creator ? productCategory.creator.name : 'N/A' }}</TableCell>
                <TableCell>{{ productCategory.updater ? productCategory.updater.name : 'N/A' }}</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="secondary" class="w-[130px]">Select Action</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[130px]">
                      <DropdownMenuItem @click="editProductCategory(productCategory)">
                        <Pencil class="mr-2 h-4 w-4" />
                        <span>Edit</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem @click="showDeleteDialog(productCategory)" class="text-destructive focus:text-destructive">
                        <Trash class="mr-2 h-4 w-4" />
                        <span>Delete</span>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
              <TableRow v-if="productCategories.length === 0">
                <TableCell colspan="7" class="h-24 text-center">
                  No product categories found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </CardContent>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="!!productCategoryToDelete" @update:open="closeDeleteDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete Product Category</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this product category? This action cannot be undone.
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
    title: 'Product Categories',
    href: '/product-categories',
  },
];

interface ProductCategory {
  id: string;
  category_name: string;
  active: boolean;
  approved: boolean;
  creator?: { name: string };
  updater?: { name: string };
  approver?: { name: string };
  parent?: string;
}

const productCategories: Ref<ProductCategory[]> = ref([]);
const isEditing = ref(false);
const productCategoryToDelete: Ref<ProductCategory | null> = ref(null);

const form = ref({
  id: undefined as string | undefined,
  category_name: '',
  active: true,
  approved: false,
  parent: null as string | null,
});

const sortProductCategoriesHierarchically = (categories: ProductCategory[]): ProductCategory[] => {
  const categoryMap = new Map<string, ProductCategory>();
  categories.forEach(category => categoryMap.set(category.id, category));

  const sortedCategories: ProductCategory[] = [];

  const addCategoryWithChildren = (category: ProductCategory) => {
    sortedCategories.push(category);
    const children = categories.filter(c => c.parent === category.id);
    children.forEach(addCategoryWithChildren);
  };

  categories.filter(category => !category.parent).forEach(addCategoryWithChildren);

  return sortedCategories;
};

const getIndentationLevel = (productCategory: ProductCategory): number => {
  let level = 0;
  let currentCategory = productCategory;
  while (currentCategory.parent) {
    level++;
    currentCategory = productCategories.value.find(c => c.id === currentCategory.parent) || currentCategory;
  }
  return level * 1.5;
};

const fetchProductCategories = async () => {
  try {
    const response = await axios.get('/api/product-categories');
    productCategories.value = sortProductCategoriesHierarchically(response.data);
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to fetch product categories',
      variant: 'destructive',
    });
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await axios.put(`/api/product-categories/${form.value.id}`, form.value);
      toast({
        title: 'Success',
        description: 'Product category updated successfully!',
      });
    } else {
      await axios.post('/api/product-categories', form.value);
      toast({
        title: 'Success',
        description: 'Product category created successfully!',
      });
    }
    await fetchProductCategories();
    resetForm();
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Operation failed',
      variant: 'destructive',
    });
  }
};

const editProductCategory = (productCategory: ProductCategory) => {
  form.value = {
    id: productCategory.id,
    category_name: productCategory.category_name,
    active: productCategory.active,
    approved: productCategory.approved,
    parent: productCategory.parent || null,
  };
  isEditing.value = true;
};

const showDeleteDialog = (productCategory: ProductCategory) => {
  productCategoryToDelete.value = productCategory;
};

const closeDeleteDialog = () => {
  productCategoryToDelete.value = null;
};

const confirmDelete = async () => {
  if (!productCategoryToDelete.value) return;
  
  try {
    await axios.delete(`/api/product-categories/${productCategoryToDelete.value.id}`);
    await fetchProductCategories();
    toast({
      title: 'Success',
      description: 'Product category deleted successfully!',
    });
  } catch (error: any) {
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete product category',
      variant: 'destructive',
    });
  } finally {
    closeDeleteDialog();
  }
};

const resetForm = () => {
  form.value = {
    id: undefined,
    category_name: '',
    active: true,
    approved: false,
    parent: null,
  };
  isEditing.value = false;
};

onMounted(() => {
  fetchProductCategories();
});
</script>

<style scoped>
/* Add your styles here */
</style> 