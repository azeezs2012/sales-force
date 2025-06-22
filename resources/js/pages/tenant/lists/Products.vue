<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Card class="flex h-full flex-1 flex-col bg-muted/10">
            <CardHeader>
                <CardTitle class="text-2xl">Product Management</CardTitle>
            </CardHeader>
            <CardContent>
                <!-- Create/Edit Form -->
                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    <Input v-model="form.product_name" placeholder="Product Name" />
                    <Select v-model="form.inventory_type">
                        <SelectTrigger>
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="Service">Service</SelectItem>
                            <SelectItem value="Inventory">Inventory</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="form.product_type_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="item in productTypes" :key="item.id" :value="item.id">
                                {{ item.type_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="form.product_category_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="item in productCategories" :key="item.id" :value="item.id">
                                {{ item.category_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="form.parent_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select parent" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="null">None</SelectItem>
                            <SelectItem v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.product_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="form.sales_account_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select sales account" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.account_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="form.expense_account_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select expense account" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.account_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-if="form.inventory_type === 'Inventory'" v-model="form.inventory_account_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select inventory account" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.account_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Input v-model="form.cost" type="number" placeholder="Cost" />
                    <Input v-model="form.price" type="number" placeholder="Price" />
                    <Input v-model="form.product_description" placeholder="Product description" class="md:col-span-3 lg:col-span-4 xl:col-span-5" />
                    <div class="flex items-end">
                      <Button @click="handleSubmit" class="w-fit whitespace-nowrap">
                          {{ isEditing ? 'Update' : 'Create' }} Product
                      </Button>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="rounded-md border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product Name</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Inventory Type</TableHead>
                                <TableHead>Price</TableHead>
                                <TableHead>Cost</TableHead>
                                <TableHead class="w-[100px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="product in products" :key="product.id">
                                <TableCell>{{ product.product_name }}</TableCell>
                                <TableCell>{{ product.productType?.type_name }}</TableCell>
                                <TableCell>{{ product.productCategory?.category_name }}</TableCell>
                                <TableCell>
                                  <Badge :variant="product.inventory_type === 'Inventory' ? 'default' : 'secondary'">
                                    {{ product.inventory_type }}
                                  </Badge>
                                </TableCell>
                                <TableCell>{{ product.price }}</TableCell>
                                <TableCell>{{ product.cost }}</TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" class="h-8 w-8 p-0">
                                                <span class="sr-only">Open menu</span>
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="editProduct(product)">Edit</DropdownMenuItem>
                                            <DropdownMenuItem @click="showDeleteDialog(product)" class="text-destructive focus:text-destructive">Delete</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="products.length === 0">
                                <TableCell colspan="7" class="h-24 text-center">
                                    No products found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="!!productToDelete" @update:open="closeDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Product</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this product? This action cannot be undone.
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
import { MoreHorizontal } from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const { toast } = useToast();

const breadcrumbs = [{ title: 'Products', href: '/products' }];

interface ProductType { id: string; type_name: string; }
interface ProductCategory { id: string; category_name: string; }
interface Account { id: string; account_name: string; }
interface Product {
    id: string;
    product_name: string;
    product_description?: string;
    inventory_type: 'Service' | 'Inventory';
    parent_id?: string;
    product_type_id: string;
    product_category_id: string;
    cost: number;
    price: number;
    sales_account_id: string;
    expense_account_id: string;
    inventory_account_id?: string;
    productType?: ProductType;
    productCategory?: ProductCategory;
}

const products: Ref<Product[]> = ref([]);
const productTypes: Ref<ProductType[]> = ref([]);
const productCategories: Ref<ProductCategory[]> = ref([]);
const accounts: Ref<Account[]> = ref([]);
const isEditing = ref(false);
const productToDelete: Ref<Product | null> = ref(null);

const initialFormState = {
    id: undefined as string | undefined,
    product_name: '',
    product_description: undefined as string | undefined,
    inventory_type: 'Service' as 'Service' | 'Inventory',
    parent_id: undefined as string | undefined,
    product_type_id: undefined as string | undefined,
    product_category_id: undefined as string | undefined,
    cost: 0,
    price: 0,
    sales_account_id: undefined as string | undefined,
    expense_account_id: undefined as string | undefined,
    inventory_account_id: undefined as string | undefined,
};

const form = ref({ ...initialFormState });

const fetchData = async () => {
    try {
        const [productsRes, typesRes, categoriesRes, accountsRes] = await Promise.all([
            axios.get('/api/products'),
            axios.get('/api/product-types'),
            axios.get('/api/product-categories'),
            axios.get('/api/accounts'),
        ]);
        products.value = productsRes.data;
        productTypes.value = typesRes.data;
        productCategories.value = categoriesRes.data;
        accounts.value = accountsRes.data;
    } catch (error: any) {
        toast({ title: 'Error', description: error.response?.data?.message || 'Failed to fetch data', variant: 'destructive' });
    }
};

const resetForm = () => {
    form.value = { ...initialFormState };
    isEditing.value = false;
};

const handleSubmit = async () => {
    try {
        if (isEditing.value) {
            await axios.put(`/api/products/${form.value.id}`, form.value);
            toast({ title: 'Success', description: 'Product updated successfully!' });
        } else {
            await axios.post('/api/products', form.value);
            toast({ title: 'Success', description: 'Product created successfully!' });
        }
        await fetchData();
        resetForm();
    } catch (error: any) {
        toast({ title: 'Error', description: error.response?.data?.message || 'Operation failed', variant: 'destructive' });
    }
};

const editProduct = (product: Product) => {
    isEditing.value = true;
    form.value = { 
        ...initialFormState,
        ...product,
        cost: Number(product.cost),
        price: Number(product.price),
     };
};

const showDeleteDialog = (product: Product) => { productToDelete.value = product; };
const closeDeleteDialog = () => { productToDelete.value = null; };

const confirmDelete = async () => {
    if (!productToDelete.value) return;
    try {
        await axios.delete(`/api/products/${productToDelete.value.id}`);
        await fetchData();
        toast({ title: 'Success', description: 'Product deleted successfully!' });
        closeDeleteDialog();
    } catch (error: any) {
        toast({ title: 'Error', description: error.response?.data?.message || 'Failed to delete product', variant: 'destructive' });
    }
};

onMounted(fetchData);
</script> 