<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/TenantAppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { PlusCircle, Save, Trash2, X, ChevronsUpDown, MoreHorizontal } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import axios from 'axios';

const { toast } = useToast();

const breadcrumbs = [{ title: 'Goods Receive Notes', href: '/grns' }];

const grns = ref([]);
const suppliers = ref([]);
const locations = ref([]);
const products = ref([]);
const accounts = ref([]);

const isFormVisible = ref(false);
const isEditing = ref(false);
const grnToDelete = ref(null);

const form = useForm({
    id: null,
    grn_date: new Date().toISOString().substr(0, 10),
    supplier_id: null,
    location_id: null,
    ap_account_id: null,
    grn_billing_address: '',
    grn_delivery_address: '',
    grn_status: 'draft',
    details: [],
});

const fetchGrns = async () => {
    try {
        console.log('Fetching GRNs...');
        const response = await axios.get('/api/grns');
        console.log('GRNs response:', response.data);
        grns.value = response.data;
    } catch (error) {
        console.error('Error fetching GRNs:', error);
        toast({ title: 'Error', description: 'Failed to fetch GRNs.', variant: 'destructive' });
    }
};

const fetchDropdownData = async () => {
    try {
        const [supplierRes, locationRes, productRes, accountRes] = await Promise.all([
            axios.get('/api/suppliers'),
            axios.get('/api/locations'),
            axios.get('/api/products'),
            axios.get('/api/accounts')
        ]);
        suppliers.value = supplierRes.data;
        locations.value = locationRes.data;
        products.value = productRes.data;
        accounts.value = accountRes.data;
    } catch (error) {
        console.log(error);
        toast({ title: 'Error', description: 'Failed to fetch dropdown data.', variant: 'destructive' });
    }
};

onMounted(() => {
    fetchGrns();
    fetchDropdownData();

    const urlParams = new URLSearchParams(window.location.search);
    const poIds = urlParams.get('po_ids');
    if (poIds) {
        createGrnFromPos(poIds);
    }
});

const createGrnFromPos = async (poIds) => {
    try {
        const response = await axios.get(`/api/po-details-for-grn?po_ids=${poIds}`);
        const { supplier_id, details } = response.data;
        
        form.reset();
        isEditing.value = false;
        
        form.supplier_id = supplier_id;
        
        form.details = details.map(d => ({
            id: null,
            product_id: d.product_id,
            location_id: d.location_id,
            quantity: d.quantity - d.received_quantity,
            cost: d.cost,
            total: (d.quantity - d.received_quantity) * d.cost,
            purchase_order_detail_id: d.id,
            product: d.product,
            ordered_quantity: d.ordered_quantity ?? d.quantity,
        }));
        
        isFormVisible.value = true;
    } catch (error) {
        toast({
            title: 'Error',
            description: error.response?.data?.error || 'Failed to fetch PO details for GRN.',
            variant: 'destructive',
        });
    }
};

const grandTotal = computed(() => {
    return form.details.reduce((total, detail) => {
        const quantity = Number(detail.quantity) || 0;
        const cost = Number(detail.cost) || 0;
        return total + (quantity * cost);
    }, 0);
});

const formatCurrency = (value) => {
    return (Number(value) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateString) => new Date(dateString).toLocaleDateString();

const addDetailRow = () => {
    form.details.push({
        id: null,
        product_id: null,
        location_id: form.location_id,
        quantity: 1,
        cost: 0,
        total: 0,
        purchase_order_detail_id: null,
        product: null,
        ordered_quantity: 0,
    });
};

const removeDetailRow = (index) => {
    form.details.splice(index, 1);
};

const showCreateForm = () => {
    isEditing.value = false;
    form.reset();
    form.grn_date = new Date().toISOString().substr(0, 10);
    form.details = [];
    addDetailRow();
    isFormVisible.value = true;
};

const editGrn = async (grnId) => {
    isEditing.value = true;
    try {
        const response = await axios.get(`/api/grns/${grnId}`);
        const data = response.data;
        form.id = data.id;
        form.grn_date = data.grn_date;
        form.supplier_id = data.supplier_id;
        form.location_id = data.location_id;
        form.ap_account_id = data.ap_account_id;
        form.grn_billing_address = data.grn_billing_address;
        form.grn_delivery_address = data.grn_delivery_address;
        form.grn_status = data.grn_status;
        form.details = data.details.map(d => ({ ...d, total: d.quantity * d.cost }));
        isFormVisible.value = true;
    } catch (error) {
        toast({ title: 'Error', description: 'Failed to fetch GRN details.', variant: 'destructive' });
    }
};

const hideForm = () => {
    isFormVisible.value = false;
    form.reset();
};

const saveGrn = async () => {
    const method = isEditing.value ? 'put' : 'post';
    const url = isEditing.value ? `/api/grns/${form.id}` : '/api/grns';

    try {
        await axios[method](url, form.data());
        toast({ title: 'Success', description: `GRN ${isEditing.value ? 'updated' : 'created'} successfully.` });
        fetchGrns();
        hideForm();
    } catch (error) {
        const errorMessage = error.response?.data?.message || `Failed to ${isEditing.value ? 'update' : 'create'} GRN.`;
        toast({ title: 'Error', description: errorMessage, variant: 'destructive' });
    }
};

const showDeleteDialog = (grn) => {
    grnToDelete.value = grn;
};

const closeDeleteDialog = () => {
    grnToDelete.value = null;
};

const confirmDelete = async () => {
    if (!grnToDelete.value) return;
    try {
        await axios.delete(`/api/grns/${grnToDelete.value.id}`);
        toast({ title: 'Success', description: 'GRN deleted successfully.' });
        fetchGrns();
        closeDeleteDialog();
    } catch (error) {
        toast({ title: 'Error', description: 'Failed to delete GRN.', variant: 'destructive' });
    }
};

</script>

<template>
    <Head title="Goods Receive Notes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col h-full">
            <!-- GRN List View -->
            <Card v-if="!isFormVisible" class="flex-1">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Goods Receive Notes</CardTitle>
                        <Button @click="showCreateForm">Create New GRN</Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>GRN #</TableHead>
                                <TableHead>Supplier</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Total</TableHead>
                                <TableHead class="w-[100px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="grn in grns" :key="grn.id">
                                <TableCell>{{ formatDate(grn.grn_date) }}</TableCell>
                                <TableCell>GRN-{{ grn.id }}</TableCell>
                                <TableCell>{{ grn.supplier?.user?.name }}</TableCell>
                                <TableCell><Badge>{{ grn.grn_status }}</Badge></TableCell>
                                <TableCell>{{ formatCurrency(grn.total_amount) }}</TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child><Button variant="ghost" class="h-8 w-8 p-0"><MoreHorizontal class="h-4 w-4" /></Button></DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="editGrn(grn.id)">Edit</DropdownMenuItem>
                                            <DropdownMenuItem @click="showDeleteDialog(grn)" class="text-destructive focus:text-destructive">Delete</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="grns.length === 0">
                                <TableCell colspan="6" class="h-24 text-center">No goods receive notes found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- GRN Create/Edit Form -->
            <div v-else class="flex flex-col gap-4">
                <Card>
                    <CardHeader><CardTitle>{{ isEditing ? 'Edit' : 'Create' }} GRN</CardTitle></CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex flex-col space-y-1.5">
                            <Label>Date</Label>
                            <Input v-model="form.grn_date" type="date" />
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
                        <div class="flex flex-col space-y-1.5">
                            <Label>AP Account</Label>
                            <Select v-model="form.ap_account_id">
                                <SelectTrigger><SelectValue placeholder="Select AP Account" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="a in accounts" :key="a.id" :value="a.id">{{ a.account_name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col space-y-1.5 md:col-span-3">
                            <Label>Delivery Address</Label>
                            <Textarea v-model="form.grn_delivery_address" placeholder="Delivery Address" />
                        </div>
                        <div class="flex flex-col space-y-1.5 md:col-span-3">
                            <Label>Billing Address</Label>
                            <Textarea v-model="form.grn_billing_address" placeholder="Billing Address" />
                        </div>
                         <div class="flex flex-col space-y-1.5">
                            <Label>Status</Label>
                            <Select v-model="form.grn_status">
                                <SelectTrigger><SelectValue placeholder="Select Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="posted">Posted</SelectItem>
                                </SelectContent>
                            </Select>
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
                                    <TableHead>PO Line ID</TableHead>
                                    <TableHead>PO Line Qty</TableHead>
                                    <TableHead class="w-[50px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, index) in form.details" :key="index">
                                    <TableCell>
                                        <Select v-model="item.product_id" :disabled="!!item.purchase_order_detail_id">
                                            <SelectTrigger><SelectValue placeholder="Select product" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-if="item.product" :value="item.product.id" >{{ item.product.product_name }}</SelectItem>
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
                                    <TableCell><Input v-model="item.quantity" type="number" placeholder="Qty" /></TableCell>
                                    <TableCell><Input v-model="item.cost" type="number" placeholder="Cost"/></TableCell>
                                    <TableCell>{{ formatCurrency(item.quantity * item.cost) }}</TableCell>
                                    <TableCell>{{ item.purchase_order_detail_id || '-' }}</TableCell>
                                    <TableCell>{{ item.ordered_quantity !== undefined ? item.ordered_quantity : '-' }}</TableCell>
                                    <TableCell><Button variant="destructive" size="sm" @click="removeDetailRow(index)"><Trash2 class="h-4 w-4" /></Button></TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <Button class="mt-4" @click="addDetailRow">Add Product</Button>
                    </CardContent>
                </Card>

                <div class="flex justify-between items-center p-4 rounded-lg bg-card border">
                    <div><CardTitle>Total: {{ formatCurrency(grandTotal) }}</CardTitle></div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="hideForm">Cancel</Button>
                        <Button @click="saveGrn">Save GRN</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="!!grnToDelete" @update:open="closeDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Goods Receive Note</DialogTitle>
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