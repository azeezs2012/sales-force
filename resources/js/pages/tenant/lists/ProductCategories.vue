<template>
  <Head title="Product Categories" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div :class="{'dark': appearance === 'dark'}" class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 bg-white dark:bg-neutral-800">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <div class="absolute top-0 left-0 py-12">
          <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-neutral-700 overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 text-gray-900 dark:text-neutral-100">
                <h2 class="text-2xl font-bold mb-4">Product Category Management</h2>

                <!-- Form to create or update a product category -->
                <form @submit.prevent="handleSubmit" class="mb-6">
                  <div class="flex items-center gap-4">
                    <input type="text" v-model="form.category_name" placeholder="Category Name" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" required />
                    <input type="checkbox" v-model="form.active" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Active
                    <input type="checkbox" v-model="form.approved" class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100" /> Approved
                    <DropdownMenu>
                      <DropdownMenuTrigger class="border rounded p-2 bg-white dark:bg-neutral-700 text-black dark:text-neutral-100">
                        {{ form.parent ? productCategories.find(category => category.id === form.parent)?.category_name : 'Select Parent Category' }}
                      </DropdownMenuTrigger>
                      <DropdownMenuContent>
                        <DropdownMenuItem @click="form.parent = null">None</DropdownMenuItem>
                        <DropdownMenuItem v-for="category in productCategories" :key="category.id" @click="form.parent = category.id">
                          {{ category.category_name }}
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                    <button type="submit" class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">{{ isEditing ? 'Update' : 'Create' }} Product Category</button>
                  </div>
                </form>

                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Category Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Approved By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Created By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Updated By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-neutral-300 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-700 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="productCategory in productCategories" :key="productCategory.id">
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          <span :style="{ paddingLeft: `${getIndentationLevel(productCategory)}rem` }">
                            <span v-if="productCategory.parent">• </span>{{ productCategory.category_name }}
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ productCategory.active ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">{{ productCategory.approved ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ productCategory.approver ? productCategory.approver.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ productCategory.creator ? productCategory.creator.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-black dark:text-neutral-100">
                          {{ productCategory.updater ? productCategory.updater.name : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <DropdownMenu>
                            <DropdownMenuTrigger class="bg-neutral-500 text-white px-4 py-2 rounded hover:bg-neutral-600 dark:bg-neutral-600 dark:hover:bg-neutral-500">Select Action</DropdownMenuTrigger>
                            <DropdownMenuContent>
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem @click="() => editProductCategory(productCategory)">Edit</DropdownMenuItem>
                              <DropdownMenuItem @click="() => deleteProductCategory(productCategory.id)">Delete</DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
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
import { ref, onMounted, defineComponent, h, VNode } from 'vue';
import axios from 'axios';
import type { Ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const { toast } = useToast()

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
const form: Ref<Partial<ProductCategory>> = ref({
  id: undefined,
  category_name: '',
  active: true,
  approved: false,
  parent: undefined,
});
const isEditing = ref(false);

const { appearance, updateAppearance } = useAppearance();

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

const fetchProductCategories = async () => {
  try {
    const response = await axios.get('/api/product-categories');
    const allCategories = response.data;
    productCategories.value = sortProductCategoriesHierarchically(allCategories);
  } catch (error) {
    toast({
      title: 'Error',
      description: 'Failed to fetch product categories.',
      variant: 'destructive',
    });
  }
};

const handleSubmit = async () => {
  try {
    if (isEditing.value) {
      await updateProductCategory();
    } else {
      await createProductCategory();
    }
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'An error occurred.',
      variant: 'destructive',
    });
  }
};

const createProductCategory = async () => {
  try {
    await axios.post('/api/product-categories', form.value);
    fetchProductCategories();
    resetForm();
    toast({
      title: 'Success',
      description: 'Product category created successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to create product category.',
      variant: 'destructive',
    });
  }
};

const editProductCategory = (productCategory: ProductCategory) => {
  form.value = { ...productCategory };
  isEditing.value = true;
};

const updateProductCategory = async () => {
  try {
    await axios.put(`/api/product-categories/${form.value.id}`, form.value);
    fetchProductCategories();
    resetForm();
    toast({
      title: 'Success',
      description: 'Product category updated successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to update product category.',
      variant: 'destructive',
    });
  }
};

const deleteProductCategory = async (id: string) => {
  try {
    await axios.delete(`/api/product-categories/${id}`);
    fetchProductCategories();
    toast({
      title: 'Success',
      description: 'Product category deleted successfully!',
      variant: 'default',
    });
  } catch (err) {
    const error = err as any;
    toast({
      title: 'Error',
      description: error.response?.data?.message || 'Failed to delete product category.',
      variant: 'destructive',
    });
  }
};

const resetForm = () => {
  form.value = { id: undefined, category_name: '', active: true, approved: false, parent: undefined };
  isEditing.value = false;
};

const getIndentationLevel = (productCategory: ProductCategory): number => {
  let level = 0;
  let currentCategory = productCategory;
  while (currentCategory.parent) {
    level++;
    currentCategory = productCategories.value.find(c => c.id === currentCategory.parent) || currentCategory;
  }
  return level * 1.5; // Adjust multiplier for desired indentation
};

onMounted(() => {
  fetchProductCategories();
});
</script>

<style scoped>
/* Add your styles here */
</style> 