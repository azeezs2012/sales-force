<template>
    <Head title="Purchase Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col h-full">
            <!-- PO List View -->
            <Card v-if="!isFormVisible" class="flex-1">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Purchase Orders</CardTitle>
                        <div class="flex items-center gap-2">
                            <Button @click="createGrnFromPos" :disabled="!canCreateGrn">Create GRN</Button>
                            <Button @click="showCreateForm">Create New PO</Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-10">
                                     <Checkbox @update:checked="(checked) => {
                                        if (checked) {
                                            selectedPoIds = purchaseOrders.map(po => po.id)
                                        } else {
                                            selectedPoIds = []
                                        }
                                    }" />
                                </TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>PO #</TableHead>
                                <TableHead>Supplier</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Total</TableHead>
                                <TableHead class="w-[100px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="po in purchaseOrders" :key="po.id">
                                <TableCell>
                                    <Checkbox :checked="selectedPoIds.includes(po.id)" @update:checked="(checked) => {
                                        if (checked) {
                                            selectedPoIds.push(po.id)
                                        } else {
                                            selectedPoIds = selectedPoIds.filter(id => id !== po.id)
                                        }
                                    }" />
                                </TableCell>
                                <TableCell>{{ formatDate(po.po_date) }}</TableCell>
                                <TableCell>PO-{{ po.id }}</TableCell>
                                <TableCell>{{ po.supplier?.user?.name }}</TableCell>
                                <TableCell><Badge>{{ po.po_status }}</Badge></TableCell>
                                <TableCell>{{ formatCurrency(po.total_amount) }}</TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child><Button variant="ghost" class="h-8 w-8 p-0"><MoreHorizontal class="h-4 w-4" /></Button></DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="editPurchaseOrder(po.id)">Edit</DropdownMenuItem>
                                            <DropdownMenuItem @click="showDeleteDialog(po)" class="text-destructive focus:text-destructive">Delete</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="purchaseOrders.length === 0">
                                <TableCell colspan="6" class="h-24 text-center">No purchase orders found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- PO Create/Edit Form -->
            <div v-else class="flex flex-col gap-4">
                <Card>
                    <CardHeader><CardTitle>{{ isEditing ? 'Edit' : 'Create' }} Purchase Order</CardTitle></CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex flex-col space-y-1.5">
                            <Label>Date</Label>
                            <Input v-model="form.po_date" type="date" />
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <Label>Supplier</Label>
                            <Select v-model="form.supplier_id">
                                <SelectTrigger><SelectValue placeholder="Select supplier" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.user?.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <Label>Location</Label>
                            <Select v-model="form.location_id">
                                <SelectTrigger><SelectValue placeholder="Select location" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="l in locations" :key="l.id" :value="l.id">{{ l.location_name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col space-y-1.5 md:col-span-3">
                            <Label>Delivery Address</Label>
                            <Input v-model="form.po_delivery_address" placeholder="Delivery Address" />
                        </div>
                        <div class="flex flex-col space-y-1.5 md:col-span-3">
                            <Label>Billing Address</Label>
                            <Input v-model="form.po_billing_address" placeholder="Billing Address" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Products</CardTitle></CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-2/5">Product</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Cost</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead class="w-[50px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, index) in form.details" :key="index">
                                    <TableCell>
                                        <Select v-model="item.product_id">
                                            <SelectTrigger><SelectValue placeholder="Select product" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="p in products" :key="p.id" :value="p.id">{{ p.product_name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </TableCell>
                                    <TableCell>
                                        <Select v-model="item.location_id">
                                            <SelectTrigger><SelectValue placeholder="Select location" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="l in locations" :key="l.id" :value="l.id">{{ l.location_name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </TableCell>
                                    <TableCell><Input v-model="item.quantity" type="number" placeholder="Qty" @input="updateTotal(item)"/></TableCell>
                                    <TableCell><Input v-model="item.cost" type="number" placeholder="Cost" @input="updateTotal(item)"/></TableCell>
                                    <TableCell>{{ formatCurrency(item.total) }}</TableCell>
                                    <TableCell><Button variant="destructive" size="sm" @click="removeDetail(index)"><Trash class="h-4 w-4" /></Button></TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <Button class="mt-4" @click="addDetail">Add Product</Button>
                    </CardContent>
                </Card>

                <div class="flex justify-between items-center p-4 rounded-lg bg-card border">
                    <div><CardTitle>Total: {{ formatCurrency(grandTotal) }}</CardTitle></div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="hideForm">Cancel</Button>
                        <Button @click="handleSubmit">Save Purchase Order</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="!!poToDelete" @update:open="closeDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Purchase Order</DialogTitle>
                    <DialogDescription>Are you sure? This action cannot be undone.</DialogDescription>
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
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/TenantAppLayout.vue';
import { ref, onMounted, computed } from 'vue';
import type { Ref } from 'vue';
import axios from 'axios';
import { useToast } from '@/components/ui/toast/use-toast';
import { MoreHorizontal, Trash } from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';

const { toast } = useToast();
const breadcrumbs = [{ title: 'Purchase Orders', href: '/purchase-orders' }];

// Interfaces
interface User { id: string; name: string; }
interface Supplier { id: string; user: User; }
interface Location { id: string; location_name: string; }
interface Product { id: string; product_name: string; }
interface PurchaseOrderDetail {
    id?: string;
    product_id: string;
    location_id: string;
    quantity: number;
    cost: number;
    total: number;
}
interface PurchaseOrder {
    id: string;
    po_date: string;
    supplier_id: string;
    location_id: string;
    po_billing_address: string;
    po_delivery_address: string;
    po_status: string;
    total_amount: number;
    supplier?: Supplier;
    details: PurchaseOrderDetail[];
}

// State
const purchaseOrders: Ref<PurchaseOrder[]> = ref([]);
const suppliers: Ref<Supplier[]> = ref([]);
const locations: Ref<Location[]> = ref([]);
const products: Ref<Product[]> = ref([]);
const isFormVisible = ref(false);
const isEditing = ref(false);
const poToDelete: Ref<PurchaseOrder | null> = ref(null);
const selectedPoIds = ref<string[]>([]);

const initialFormState: PurchaseOrder = {
    id: '',
    po_date: new Date().toISOString().split('T')[0],
    supplier_id: '',
    location_id: '',
    po_billing_address: '',
    po_delivery_address: '',
    po_status: 'Draft',
    total_amount: 0,
    details: [],
};
const form = ref({ ...initialFormState });

// Computed
const grandTotal = computed(() => {
    return form.value.details.reduce((sum, item) => {
        return sum + (Number(item.total) || 0);
    }, 0);
});

const canCreateGrn = computed(() => selectedPoIds.value.length > 0);

const createGrnFromPos = () => {
    if (!canCreateGrn.value) return;
    const poIds = selectedPoIds.value.join(',');
    router.get(`/grns?po_ids=${poIds}`);
};

// Methods
const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString();
const formatCurrency = (amount: number) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount || 0);

const fetchPOs = async () => {
    try {
        const response = await axios.get('/api/purchase-orders');
        purchaseOrders.value = response.data;
    } catch (error) {
        toast({ title: 'Error', description: 'Failed to fetch purchase orders.', variant: 'destructive' });
    }
};

const fetchDropdownData = async () => {
    try {
        const [suppliersRes, locationsRes, productsRes] = await Promise.all([
            axios.get('/api/suppliers'),
            axios.get('/api/locations'),
            axios.get('/api/products'),
        ]);
        suppliers.value = suppliersRes.data;
        locations.value = locationsRes.data;
        products.value = productsRes.data;
    } catch (error) {
        toast({ title: 'Error', description: 'Failed to fetch dropdown data.', variant: 'destructive' });
    }
};

const showCreateForm = () => {
    isEditing.value = false;
    form.value = { ...initialFormState, details: [ { product_id: '', location_id: '', quantity: 1, cost: 0, total: 0 } ] };
    isFormVisible.value = true;
};

const hideForm = () => { isFormVisible.value = false; };

const addDetail = () => {
    form.value.details.push({ product_id: '', location_id: form.value.location_id, quantity: 1, cost: 0, total: 0 });
};
const removeDetail = (index: number) => {
    form.value.details.splice(index, 1);
};
const updateTotal = (item: PurchaseOrderDetail) => {
    item.total = (Number(item.quantity) || 0) * (Number(item.cost) || 0);
};

const handleSubmit = async () => {
    try {
        if (isEditing.value) {
            await axios.put(`/api/purchase-orders/${form.value.id}`, form.value);
            toast({ title: 'Success', description: 'Purchase Order updated successfully!' });
        } else {
            await axios.post('/api/purchase-orders', form.value);
            toast({ title: 'Success', description: 'Purchase Order created successfully!' });
        }
        await fetchPOs();
        hideForm();
    } catch (error: any) {
        toast({ title: 'Error', description: error.response?.data?.message || 'Operation failed', variant: 'destructive' });
    }
};

const editPurchaseOrder = async (id: string) => {
    try {
        const response = await axios.get(`/api/purchase-orders/${id}`);
        isEditing.value = true;
        form.value = {
            ...response.data,
            po_date: new Date(response.data.po_date).toISOString().split('T')[0],
        };
        isFormVisible.value = true;
    } catch (error) {
        toast({ title: 'Error', description: 'Failed to fetch purchase order details.', variant: 'destructive' });
    }
};

const showDeleteDialog = (po: PurchaseOrder) => { poToDelete.value = po; };
const closeDeleteDialog = () => { poToDelete.value = null; };

const confirmDelete = async () => {
    if (!poToDelete.value) return;
    try {
        await axios.delete(`/api/purchase-orders/${poToDelete.value.id}`);
        await fetchPOs();
        toast({ title: 'Success', description: 'Purchase Order deleted successfully!' });
        closeDeleteDialog();
    } catch (error: any) {
        toast({ title: 'Error', description: error.response?.data?.message || 'Failed to delete PO.', variant: 'destructive' });
    }
};

onMounted(() => {
    fetchPOs();
    fetchDropdownData();
});
</script> 