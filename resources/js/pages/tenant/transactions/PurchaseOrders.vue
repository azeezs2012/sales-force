<template>
    <Head title="Purchase Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col h-full">
            <!-- PO List View -->
            <Card v-if="!isFormVisible" class="flex-1">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Purchase Orders</CardTitle>
                        <div class="flex items-center gap-4">
                            <Select v-model="statusFilter" class="w-32">
                                <SelectTrigger>
                                    <SelectValue placeholder="Filter by status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="Open">Open</SelectItem>
                                    <SelectItem value="Partial">Partial</SelectItem>
                                    <SelectItem value="Closed">Closed</SelectItem>
                                </SelectContent>
                            </Select>
                            <div class="flex items-center gap-2">
                                <Button @click="createGrnFromPos" :disabled="!canCreateGrn">Create GRN</Button>
                                <Button @click="showCreateForm">Create New PO</Button>
                            </div>
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
                                            selectedPoIds = filteredPurchaseOrders.map(po => po.id)
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
                            <TableRow v-for="po in filteredPurchaseOrders" :key="po.id">
                                <TableCell>
                                    <Checkbox
                                        :checked="selectedPoIds.includes(po.id)"
                                        :disabled="selectedSupplierId && po.supplier_id !== selectedSupplierId"
                                        @update:checked="(checked) => {
                                            if (checked) {
                                                selectedPoIds.push(po.id)
                                            } else {
                                                selectedPoIds = selectedPoIds.filter(id => id !== po.id)
                                            }
                                        }"
                                    />
                                </TableCell>
                                <TableCell>{{ formatDate(po.po_date) }}</TableCell>
                                <TableCell>PO-{{ po.id }}</TableCell>
                                <TableCell>{{ po.supplier?.user?.name }}</TableCell>
                                <TableCell>
                                    <Badge 
                                        :variant="po.po_status === 'Closed' ? 'destructive' : po.po_status === 'Partial' ? 'secondary' : 'default'"
                                        :class="po.po_status === 'Partial' ? 'bg-orange-500 text-white hover:bg-orange-500' : po.po_status === 'Closed' ? 'hover:bg-destructive' : 'hover:bg-primary'"
                                    >
                                        {{ po.po_status }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ formatCurrency(po.total_amount) }}</TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child><Button variant="ghost" class="h-8 w-8 p-0"><MoreHorizontal class="h-4 w-4" /></Button></DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="editPurchaseOrder(po.id)">Edit</DropdownMenuItem>
                                            <DropdownMenuItem @click="showDeleteConfirm(po)" class="text-destructive focus:text-destructive">Delete</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="filteredPurchaseOrders.length === 0">
                                <TableCell colspan="7" class="h-24 text-center">
                                    {{ purchaseOrders.length === 0 ? 'No purchase orders found.' : 'No purchase orders match the selected filter.' }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- PO Create/Edit Form -->
            <div v-else class="flex flex-col gap-4 relative">
                <!-- Bottom Watermarks -->
                <div v-if="isPoClosed" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-10 pointer-events-none">
                    <div class="bg-red-600/95 text-white px-12 py-6 rounded-xl transform -rotate-12 text-3xl font-bold shadow-2xl border-2 border-red-700">
                        CLOSED
                    </div>
                </div>
                <div v-if="isPoPartial" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-10 pointer-events-none">
                    <div class="bg-orange-600/95 text-white px-12 py-6 rounded-xl transform -rotate-12 text-3xl font-bold shadow-2xl border-2 border-orange-700">
                        PARTIAL
                    </div>
                </div>
                <!-- Form Overlay for Closed PO -->
                <div v-if="isPoClosed" class="absolute inset-0 z-5 bg-gray-900/20 pointer-events-none rounded-lg"></div>
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>{{ isEditing ? 'Edit' : 'Create' }} Purchase Order</CardTitle>
                            <Badge v-if="isPoClosed" variant="destructive" class="text-lg px-4 py-2">
                                {{ form.po_status }}
                            </Badge>
                            <Badge v-if="isPoPartial" variant="secondary" class="text-lg px-4 py-2 bg-orange-500 text-white">
                                {{ form.po_status }}
                            </Badge>
                        </div>
                    </CardHeader>
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
                                    <TableHead>Received</TableHead>
                                    <TableHead>Remaining</TableHead>
                                    <TableHead>Cost</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead class="w-[50px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, index) in form.details" :key="index">
                                    <TableCell>
                                        <Select v-model="item.product_id" :disabled="!!item.received_quantity && item.received_quantity > 0">
                                            <SelectTrigger><SelectValue placeholder="Select product" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="p in products" :key="p.id" :value="p.id">{{ p.product_name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </TableCell>
                                    <TableCell>
                                        <Select v-model="item.location_id" :disabled="!!item.received_quantity && item.received_quantity > 0">
                                            <SelectTrigger><SelectValue placeholder="Select location" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="l in locations" :key="l.id" :value="l.id">{{ l.location_name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </TableCell>
                                    <TableCell>
                                        <Input 
                                            v-model="item.quantity" 
                                            type="number" 
                                            placeholder="Qty" 
                                            :min="item.received_quantity || 1"
                                            @input="updateTotal(item)"
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">
                                            {{ item.received_quantity || 0 }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">
                                            {{ calculateRemainingQuantity(item) }}
                                        </div>
                                    </TableCell>
                                    <TableCell><Input v-model="item.cost" type="number" placeholder="Cost" @input="updateTotal(item)"/></TableCell>
                                    <TableCell>{{ formatCurrency(item.total) }}</TableCell>
                                    <TableCell>
                                        <Button 
                                            variant="destructive" 
                                            size="sm" 
                                            @click="removeDetail(index)"
                                            :disabled="!!item.received_quantity && item.received_quantity > 0"
                                        >
                                            <Trash class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
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

        <!-- Custom Delete Confirmation Modal -->
        <div v-if="showConfirmDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm">
            <div class="bg-background border border-border rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-foreground">Delete Purchase Order</h3>
                    <button @click="hideDeleteConfirm" class="text-muted-foreground hover:text-foreground transition-colors">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <p class="text-muted-foreground mb-6">
                    Are you sure you want to delete Purchase Order <strong class="text-foreground">PO-{{ poToDelete?.id }}</strong>? 
                    This action cannot be undone.
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
    received_quantity?: number;
    remaining_quantity?: number;
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
const showConfirmDelete = ref(false);
const poToDelete = ref<PurchaseOrder | null>(null);

const selectedPoIds = ref<string[]>([]);
const statusFilter = ref<string>('all');

const initialFormState: PurchaseOrder = {
    id: '',
    po_date: new Date().toISOString().split('T')[0],
    supplier_id: '',
    location_id: '',
    po_billing_address: '',
    po_delivery_address: '',
    po_status: 'Open', // Managed by controller
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

const isPoClosed = computed(() => form.value.po_status === 'Closed');
const isPoPartial = computed(() => form.value.po_status === 'Partial');

const createGrnFromPos = () => {
    if (!canCreateGrn.value) return;
    const poIds = selectedPoIds.value.join(',');
    router.get(`/grns?po_ids=${poIds}`);
};

// Add a computed property to get the supplier ID of the first selected PO
const selectedSupplierId = computed(() => {
    const firstSelected = purchaseOrders.value.find(po => selectedPoIds.value.includes(po.id));
    return firstSelected ? firstSelected.supplier_id : null;
});

const filteredPurchaseOrders = computed(() => {
    if (statusFilter.value === 'all') {
        return purchaseOrders.value;
    }
    return purchaseOrders.value.filter(po => po.po_status === statusFilter.value);
});

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
    const initialDetail = { 
        product_id: '', 
        location_id: '', 
        quantity: 1, 
        cost: 0, 
        total: 0,
        received_quantity: 0,
        remaining_quantity: 1
    };
    initialDetail.remaining_quantity = calculateRemainingQuantity(initialDetail);
    form.value = { 
        ...initialFormState, 
        details: [initialDetail] 
    };
    isFormVisible.value = true;
};

const hideForm = () => { isFormVisible.value = false; };

const addDetail = () => {
    const newDetail = { 
        product_id: '', 
        location_id: form.value.location_id, 
        quantity: 1, 
        cost: 0, 
        total: 0,
        received_quantity: 0,
        remaining_quantity: 1
    };
    newDetail.remaining_quantity = calculateRemainingQuantity(newDetail);
    form.value.details.push(newDetail);
};
const removeDetail = (index: number) => {
    form.value.details.splice(index, 1);
};
const updateTotal = (item: PurchaseOrderDetail) => {
    item.total = (Number(item.quantity) || 0) * (Number(item.cost) || 0);
    // Update remaining quantity in real-time
    item.remaining_quantity = calculateRemainingQuantity(item);
};

const calculateRemainingQuantity = (item: PurchaseOrderDetail) => {
    const quantity = Number(item.quantity) || 0;
    const received = Number(item.received_quantity) || 0;
    return Math.max(0, quantity - received);
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
        
        // Calculate remaining quantity for each detail
        form.value.details.forEach(detail => {
            detail.remaining_quantity = calculateRemainingQuantity(detail);
        });
        
        isFormVisible.value = true;
    } catch (error) {
        toast({ title: 'Error', description: 'Failed to fetch purchase order details.', variant: 'destructive' });
    }
};

const showDeleteConfirm = (po: PurchaseOrder) => {
    poToDelete.value = po;
    showConfirmDelete.value = true;
};

const hideDeleteConfirm = () => {
    showConfirmDelete.value = false;
    poToDelete.value = null;
};

const confirmDelete = async () => {
    if (!poToDelete.value) return;
    
    try {
        await axios.delete(`/api/purchase-orders/${poToDelete.value.id}`);
        await fetchPOs();
        toast({ title: 'Success', description: 'Purchase Order deleted successfully!' });
    } catch (error: any) {
        toast({ title: 'Error', description: error.response?.data?.message || 'Failed to delete PO.', variant: 'destructive' });
    } finally {
        hideDeleteConfirm();
    }
};

onMounted(() => {
    fetchPOs();
    fetchDropdownData();
});
</script> 