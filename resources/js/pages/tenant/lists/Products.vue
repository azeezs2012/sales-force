<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Card class="flex h-full flex-1 flex-col bg-muted/10">
            <CardHeader>
                <CardTitle class="text-2xl">Product Management</CardTitle>
            </CardHeader>
            <CardContent>
                <!-- Create/Edit Form -->
                <div class="mb-6 space-y-6">
                    <!-- First Row: Inventory Type and Name -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Select v-model="form.inventory_type">
                            <SelectTrigger>
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Service">Service</SelectItem>
                                <SelectItem value="Inventory">Inventory</SelectItem>
                            </SelectContent>
                        </Select>
                        <Input v-model="form.product_name" placeholder="Product Name" />
                    </div>

                    <!-- Second Row: Parent, Type and Category -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                    </div>

                    <!-- Third Row: Cost and Cost Account -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Input 
                            v-model="form.cost" 
                            type="number" 
                            placeholder="0.00" 
                            step="0.01"
                            min="0"
                            class="text-right"
                        />
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
                    </div>

                    <!-- Fourth Row: Price and Sales Account -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Input 
                            v-model="form.price" 
                            type="number" 
                            placeholder="0.00" 
                            step="0.01"
                            min="0"
                            class="text-right"
                        />
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
                    </div>

                    <!-- Fifth Row: Inventory Account (if visible) -->
                    <div v-if="form.inventory_type === 'Inventory'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Select v-model="form.inventory_account_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select inventory account" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="account in accounts" :key="account.id" :value="account.id">
                                    {{ account.account_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div></div> <!-- Empty div for grid alignment -->
                    </div>

                    <!-- Sixth Row: Description -->
                    <div class="grid grid-cols-1 gap-4">
                        <Textarea v-model="form.product_description" placeholder="Product description" />
                    </div>

                    <!-- Seventh Row: Active and Approved -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center space-x-4">
                            <Label class="flex items-center gap-2">
                                <Switch v-model="form.active" />
                                Active
                            </Label>
                        </div>
                        <div class="flex items-center space-x-4">
                            <Label class="flex items-center gap-2">
                                <Switch v-model="form.approved" />
                                Approved
                            </Label>
                        </div>
                    </div>

                    <!-- Eighth Row: Profit Margin Indicator -->
                    <div v-if="profitMargin" class="grid grid-cols-1 gap-4">
                        <div class="flex items-center space-x-2 p-2 rounded-md" 
                             :class="profitMargin.type === 'profit' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                            <span class="text-sm font-medium" 
                                  :class="profitMargin.type === 'profit' ? 'text-green-700' : 'text-red-700'">
                                {{ profitMargin.type === 'profit' ? 'Profit' : 'Loss' }}:
                            </span>
                            <span class="text-sm font-mono" 
                                  :class="profitMargin.type === 'profit' ? 'text-green-700' : 'text-red-700'">
                                {{ formatCurrency(profitMargin.value) }}
                            </span>
                            <span class="text-xs" 
                                  :class="profitMargin.type === 'profit' ? 'text-green-600' : 'text-red-600'">
                                ({{ profitMargin.percentage.toFixed(1) }}%)
                            </span>
                        </div>
                    </div>

                    <!-- Ninth Row: Action Buttons -->
                    <div class="flex gap-2">
                        <Button @click="resetForm" class="w-fit" variant="secondary">Cancel</Button>
                        <Button @click="handleSubmit" class="w-fit">{{ isEditing ? 'Update' : 'Create' }} Product</Button>
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
                                <TableHead class="text-right">Price</TableHead>
                                <TableHead class="text-right">Cost</TableHead>
                                <TableHead class="text-right">Profit/Loss</TableHead>
                                <TableHead>Active</TableHead>
                                <TableHead>Approved</TableHead>
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
                                <TableCell class="text-right font-mono">
                                    {{ formatCurrency(product.price) }}
                                </TableCell>
                                <TableCell class="text-right font-mono">
                                    {{ formatCurrency(product.cost) }}
                                </TableCell>
                                <TableCell class="text-right font-mono">
                                    <span :class="{
                                        'text-green-600': calculateProfitLoss(product.price, product.cost).isProfit,
                                        'text-red-600': calculateProfitLoss(product.price, product.cost).isLoss,
                                        'text-gray-600': calculateProfitLoss(product.price, product.cost).isBreakEven
                                    }">
                                        {{ calculateProfitLoss(product.price, product.cost).formatted }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="product.active ? 'default' : 'secondary'">
                                        {{ product.active ? 'Yes' : 'No' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="product.approved ? 'default' : 'secondary'">
                                        {{ product.approved ? 'Yes' : 'No' }}
                                    </Badge>
                                </TableCell>
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
                                <TableCell colspan="10" class="h-24 text-center">
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
import { ref, onMounted, computed } from 'vue';
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
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';

const { toast } = useToast();

const breadcrumbs = [{ title: 'Products', href: '/products' }];

// Utility function to format currency
const formatCurrency = (value: number | string | null | undefined): string => {
    if (value === null || value === undefined || value === '') return '0.00';
    const numValue = typeof value === 'string' ? parseFloat(value) : value;
    if (isNaN(numValue)) return '0.00';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(numValue);
};

// Helper function to calculate and format profit/loss
const calculateProfitLoss = (price: number, cost: number) => {
    const profit = price - cost;
    return {
        value: profit,
        formatted: formatCurrency(profit),
        isProfit: profit > 0,
        isLoss: profit < 0,
        isBreakEven: profit === 0
    };
};

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
    active: boolean;
    approved: boolean;
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
    cost: '0' as string,
    price: '0' as string,
    sales_account_id: undefined as string | undefined,
    expense_account_id: undefined as string | undefined,
    inventory_account_id: undefined as string | undefined,
    active: true,
    approved: true,
};

const form = ref({ ...initialFormState });

// Computed property for profit margin
const profitMargin = computed(() => {
    const cost = parseFloat(form.value.cost) || 0;
    const price = parseFloat(form.value.price) || 0;
    
    if (cost === 0 || price === 0) return null;
    if (price < cost) return { type: 'loss', value: cost - price, percentage: ((cost - price) / cost * 100) };
    
    const profit = price - cost;
    const percentage = (profit / cost * 100);
    return { type: 'profit', value: profit, percentage };
});

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
        // Validate required fields
        if (!form.value.product_name.trim()) {
            toast({ title: 'Error', description: 'Product name is required', variant: 'destructive' });
            return;
        }
        
        if (!form.value.sales_account_id) {
            toast({ title: 'Error', description: 'Sales account is required', variant: 'destructive' });
            return;
        }
        
        if (!form.value.expense_account_id) {
            toast({ title: 'Error', description: 'Expense account is required', variant: 'destructive' });
            return;
        }
        
        if (form.value.inventory_type === 'Inventory' && !form.value.inventory_account_id) {
            toast({ title: 'Error', description: 'Inventory account is required for inventory products', variant: 'destructive' });
            return;
        }
        
        // Validate cost and price
        const cost = parseFloat(form.value.cost) || 0;
        const price = parseFloat(form.value.price) || 0;
        
        if (cost < 0) {
            toast({ title: 'Error', description: 'Cost cannot be negative', variant: 'destructive' });
            return;
        }
        
        if (price < 0) {
            toast({ title: 'Error', description: 'Price cannot be negative', variant: 'destructive' });
            return;
        }
        
        // Prepare form data with proper number formatting
        const formData = {
            ...form.value,
            cost: cost,
            price: price,
        };
        
        if (isEditing.value) {
            await axios.put(`/api/products/${form.value.id}`, formData);
            toast({ title: 'Success', description: 'Product updated successfully!' });
        } else {
            await axios.post('/api/products', formData);
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
        cost: String(product.cost || 0),
        price: String(product.price || 0),
        active: Boolean(product.active),
        approved: Boolean(product.approved),
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