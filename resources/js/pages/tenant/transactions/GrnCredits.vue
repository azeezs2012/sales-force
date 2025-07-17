<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/TenantAppLayout.vue';
import { ref, computed, onMounted, type Ref } from 'vue';
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

const breadcrumbs = [{ title: 'GRN Credits', href: '/grn-credits' }];

// Interfaces
interface User { id: string; name: string; }
interface Supplier { id: string; user: User; }
interface Location { id: string; location_name: string; }
interface Product { id: string; product_name: string; }
interface Account { id: string; account_name: string; }
interface GrnCreditDetail {
    id?: string;
    product_id: string;
    location_id: string;
    quantity: number;
    cost: number;
    total: number;
    grn_detail_id?: string;
    product?: Product;
    original_grn_quantity?: number;
    credited_quantity?: number;
    available_quantity?: number;
}
interface GrnCredit {
    id: string;
    grn_credit_date: string;
    supplier_id: string;
    location_id: string;
    ap_account_id: string;
    grn_credit_billing_address: string;
    grn_credit_delivery_address: string;
    grn_credit_status: string;
    credit_reason: string;
    total_amount: number;
    supplier?: Supplier;
    details: GrnCreditDetail[];
}

const grnCredits = ref([]);
const grns = ref([]);
const suppliers = ref([]);
const locations = ref([]);
const products = ref([]);
const accounts = ref([]);

const isFormVisible = ref(false);
const isEditing = ref(false);
const showConfirmDelete = ref(false);
const grnCreditToDelete = ref(null);

const statusFilter = ref<string>('all');

// Add computed properties for supplier validation
const selectedSupplierId = computed(() => {
    const firstSelected = grnCredits.value.find(credit => selectedGrnCreditIds.value.includes(credit.id));
    return firstSelected ? firstSelected.supplier_id : null;
});

const selectedGrnCreditIds = ref<string[]>([]);

const canCreateGrnCredit = computed(() => selectedGrnCreditIds.value.length > 0);

const form = useForm({
    id: null,
    grn_credit_date: new Date().toISOString().substr(0, 10),
    supplier_id: null,
    location_id: null,
    ap_account_id: null,
    grn_credit_billing_address: '',
    grn_credit_delivery_address: '',
    grn_credit_status: 'draft',
    credit_reason: '',
    details: [],
});

const fetchGrnCredits = async () => {
    try {
        console.log('Fetching GRN Credits...');
        const response = await axios.get('/api/grn-credits');
        console.log('GRN Credits response:', response.data);
        grnCredits.value = response.data;
    } catch (error) {
        console.error('Error fetching GRN Credits:', error);
        toast({ title: 'Error', description: 'Failed to fetch GRN Credits.', variant: 'destructive' });
    }
};

const fetchGrns = async () => {
    try {
        const response = await axios.get('/api/grns');
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
        // Set default AP Account if creating
        if (!isEditing.value && accounts.value.length > 0) {
            form.ap_account_id = accounts.value[0].id;
        }
    } catch (error) {
        console.log(error);
        toast({ title: 'Error', description: 'Failed to fetch dropdown data.', variant: 'destructive' });
    }
};

onMounted(() => {
    fetchGrnCredits();
    fetchGrns();
    fetchDropdownData();

    const urlParams = new URLSearchParams(window.location.search);
    const grnIds = urlParams.get('grn_ids');
    if (grnIds) {
        createGrnCreditFromGrns(grnIds);
    }
});

const createGrnCreditFromGrns = async (grnIds) => {
    try {
        const response = await axios.get(`/api/grn-details-for-credit?grn_ids=${grnIds}`);
        const { supplier_id, details } = response.data;
        
        form.reset();
        isEditing.value = false;
        
        form.supplier_id = supplier_id;
        
        // Fetch the first GRN's main details
        const firstGrnId = Array.isArray(grnIds) ? grnIds[0] : grnIds.split(',')[0];
        if (firstGrnId) {
            const grnRes = await axios.get(`/api/grns/${firstGrnId}`);
            const grn = grnRes.data;
            form.grn_credit_billing_address = grn.grn_billing_address || '';
            form.grn_credit_delivery_address = grn.grn_delivery_address || '';
            form.location_id = grn.location_id || null;
        }
        
        // Set default AP Account on load
        if (accounts.value.length > 0) {
            form.ap_account_id = accounts.value[0].id;
        }
        
        form.details = details.map(d => ({
            id: null,
            product_id: d.product_id,
            location_id: d.location_id,
            quantity: d.available_quantity,
            cost: d.cost,
            total: d.available_quantity * d.cost,
            grn_detail_id: d.id,
            product: d.product,
            original_grn_quantity: d.original_grn_quantity,
            credited_quantity: d.credited_quantity,
            available_quantity: d.available_quantity,
        }));
        
        isFormVisible.value = true;
    } catch (error) {
        toast({
            title: 'Error',
            description: error.response?.data?.error || 'Failed to fetch GRN details for credit.',
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
        grn_detail_id: null,
        product: null,
        original_grn_quantity: 0,
        credited_quantity: 0,
        available_quantity: 0,
    });
};

const removeDetailRow = (index) => {
    form.details.splice(index, 1);
};

const showCreateForm = () => {
    isEditing.value = false;
    form.reset();
    form.grn_credit_date = new Date().toISOString().substr(0, 10);
    form.details = [];
    addDetailRow();
    // Set default AP Account on load
    if (accounts.value.length > 0) {
        form.ap_account_id = accounts.value[0].id;
    }
    isFormVisible.value = true;
};

const editGrnCredit = async (grnCreditId) => {
    isEditing.value = true;
    try {
        const response = await axios.get(`/api/grn-credits/${grnCreditId}`);
        const data = response.data;
        form.id = data.id;
        form.grn_credit_date = data.grn_credit_date;
        form.supplier_id = data.supplier_id;
        form.location_id = data.location_id;
        form.ap_account_id = data.ap_account_id;
        form.grn_credit_billing_address = data.grn_credit_billing_address;
        form.grn_credit_delivery_address = data.grn_credit_delivery_address;
        form.grn_credit_status = data.grn_credit_status;
        form.credit_reason = data.credit_reason;
        form.details = data.details.map(d => ({ ...d, total: d.quantity * d.cost }));
        isFormVisible.value = true;
    } catch (error) {
        toast({ title: 'Error', description: 'Failed to fetch GRN Credit details.', variant: 'destructive' });
    }
};

const hideForm = () => {
    isFormVisible.value = false;
    form.reset();
};

const saveGrnCredit = async () => {
    const method = isEditing.value ? 'put' : 'post';
    const url = isEditing.value ? `/api/grn-credits/${form.id}` : '/api/grn-credits';

    try {
        await axios[method](url, form.data());
        toast({ title: 'Success', description: `GRN Credit ${isEditing.value ? 'updated' : 'created'} successfully.` });
        fetchGrnCredits();
        hideForm();
    } catch (error) {
        const errorMessage = error.response?.data?.message || `Failed to ${isEditing.value ? 'update' : 'create'} GRN Credit.`;
        toast({ title: 'Error', description: errorMessage, variant: 'destructive' });
    }
};

const showDeleteConfirm = (grnCredit) => {
    grnCreditToDelete.value = grnCredit;
    showConfirmDelete.value = true;
};

const hideDeleteConfirm = () => {
    showConfirmDelete.value = false;
    grnCreditToDelete.value = null;
};

const confirmDelete = async () => {
    if (!grnCreditToDelete.value) return;
    
    try {
        await axios.delete(`/api/grn-credits/${grnCreditToDelete.value.id}`);
        toast({ title: 'Success', description: 'GRN Credit deleted successfully.' });
        fetchGrnCredits();
    } catch (error) {
        toast({ title: 'Error', description: 'Failed to delete GRN Credit.', variant: 'destructive' });
    } finally {
        hideDeleteConfirm();
    }
};

const createGrnCreditFromSelectedGrns = () => {
    if (!canCreateGrnCredit.value) return;
    const grnIds = selectedGrnIds.value.join(',');
    router.get(`/grn-credits?grn_ids=${grnIds}`);
};

const filteredGrnCredits = computed(() => {
    if (statusFilter.value === 'all') {
        return grnCredits.value;
    }
    return grnCredits.value.filter(credit => credit.grn_credit_status === statusFilter.value);
});

</script>

<template>
    <Head title="GRN Credits" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col h-full">
            <!-- GRN Credit List View -->
            <Card v-if="!isFormVisible" class="flex-1">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>GRN Credits</CardTitle>
                        <div class="flex items-center gap-4">
                            <Select v-model="statusFilter" class="w-32">
                                <SelectTrigger>
                                    <SelectValue placeholder="Filter by status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="posted">Posted</SelectItem>
                                </SelectContent>
                            </Select>
                            <div class="flex items-center gap-2">
                                <Button @click="createGrnCreditFromSelectedGrns" :disabled="!canCreateGrnCredit">Create GRN Credit</Button>
                                <Button @click="showCreateForm">Create New GRN Credit</Button>
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
                                            selectedGrnCreditIds = filteredGrnCredits.map(credit => credit.id)
                                        } else {
                                            selectedGrnCreditIds = []
                                        }
                                    }" />
                                </TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>GRN Credit #</TableHead>
                                <TableHead>Supplier</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Total</TableHead>
                                <TableHead class="w-[100px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="credit in filteredGrnCredits" :key="credit.id">
                                <TableCell>
                                    <Checkbox
                                        :checked="selectedGrnCreditIds.includes(credit.id)"
                                        :disabled="selectedSupplierId && credit.supplier_id !== selectedSupplierId"
                                        @update:checked="(checked) => {
                                            if (checked) {
                                                selectedGrnCreditIds.push(credit.id)
                                            } else {
                                                selectedGrnCreditIds = selectedGrnCreditIds.filter(id => id !== credit.id)
                                            }
                                        }"
                                    />
                                </TableCell>
                                <TableCell>{{ formatDate(credit.grn_credit_date) }}</TableCell>
                                <TableCell>GRN-CR-{{ credit.id }}</TableCell>
                                <TableCell>{{ credit.supplier?.user?.name }}</TableCell>
                                <TableCell>
                                    <Badge 
                                        :variant="credit.grn_credit_status === 'posted' ? 'default' : 'secondary'"
                                        :class="credit.grn_credit_status === 'posted' ? 'bg-green-500 text-white hover:bg-green-500' : 'bg-orange-500 text-white hover:bg-orange-500'"
                                    >
                                        {{ credit.grn_credit_status }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ formatCurrency(credit.total_amount) }}</TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child><Button variant="ghost" class="h-8 w-8 p-0"><MoreHorizontal class="h-4 w-4" /></Button></DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="editGrnCredit(credit.id)">Edit</DropdownMenuItem>
                                            <DropdownMenuItem @click="showDeleteConfirm(credit)" class="text-destructive focus:text-destructive">Delete</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="filteredGrnCredits.length === 0">
                                <TableCell colspan="7" class="h-24 text-center">
                                    {{ grnCredits.length === 0 ? 'No GRN credits found.' : 'No GRN credits match the selected filter.' }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- GRN Credit Create/Edit Form -->
            <div v-else class="flex flex-col gap-4">
                <Card>
                    <CardHeader><CardTitle>{{ isEditing ? 'Edit' : 'Create' }} GRN Credit</CardTitle></CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex flex-col space-y-1.5">
                            <Label>Date</Label>
                            <Input v-model="form.grn_credit_date" type="date" />
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
                            <Label>Credit Reason</Label>
                            <Textarea v-model="form.credit_reason" placeholder="Reason for returning goods" />
                        </div>
                        <div class="flex flex-col space-y-1.5 md:col-span-3">
                            <Label>Delivery Address</Label>
                            <Textarea v-model="form.grn_credit_delivery_address" placeholder="Delivery Address" />
                        </div>
                        <div class="flex flex-col space-y-1.5 md:col-span-3">
                            <Label>Billing Address</Label>
                            <Textarea v-model="form.grn_credit_billing_address" placeholder="Billing Address" />
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
                                    <TableHead>GRN Line ID</TableHead>
                                    <TableHead>Original GRN Qty</TableHead>
                                    <TableHead class="w-[50px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, index) in form.details" :key="index">
                                    <TableCell>
                                        <Select v-model="item.product_id" :disabled="!!item.grn_detail_id">
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
                                    <TableCell>
                                        <Input
                                            v-model="item.quantity"
                                            type="number"
                                            placeholder="Qty"
                                            :min="1"
                                            :max="item.grn_detail_id ? item.available_quantity : undefined"
                                            @input="
                                                if (item.grn_detail_id && Number(item.quantity) > item.available_quantity) {
                                                    item.quantity = item.available_quantity;
                                                    toast({ title: 'Warning', description: 'Cannot exceed available GRN quantity.', variant: 'destructive' });
                                                }
                                            "
                                        />
                                    </TableCell>
                                    <TableCell><Input v-model="item.cost" type="number" placeholder="Cost"/></TableCell>
                                    <TableCell>{{ formatCurrency(item.quantity * item.cost) }}</TableCell>
                                    <TableCell>{{ item.grn_detail_id || '-' }}</TableCell>
                                    <TableCell>{{ item.original_grn_quantity !== undefined ? item.original_grn_quantity : '-' }}</TableCell>
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
                        <Button @click="saveGrnCredit">Save GRN Credit</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Delete Confirmation Modal -->
        <div v-if="showConfirmDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm">
            <div class="bg-background border border-border rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-foreground">Delete GRN Credit</h3>
                    <button @click="hideDeleteConfirm" class="text-muted-foreground hover:text-foreground transition-colors">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <p class="text-muted-foreground mb-6">
                    Are you sure you want to delete GRN Credit <strong class="text-foreground">GRN-CR-{{ grnCreditToDelete?.id }}</strong>? 
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

<style scoped>
/* Fix autofill styling */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 30px hsl(var(--background)) inset !important;
    -webkit-text-fill-color: hsl(var(--foreground)) !important;
}
</style> 