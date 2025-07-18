<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/TenantAppLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
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

const breadcrumbs = [{ title: 'GRN Payments', href: '/grn-payments' }];

// Interfaces
interface User { id: string; name: string; }
interface Supplier { id: string; user: User; }
interface Location { id: string; location_name: string; }
interface PaymentMethod { id: string; payment_method_name: string; }
interface GrnPayment {
    id: string;
    payment_date: string;
    supplier_id: string;
    payment_method_id: string;
    payment_amount: number;
    payment_reference: string;
    payment_notes: string;
    payment_status: string;
    supplier?: Supplier;
    payment_method?: PaymentMethod;
}

const grnPayments = ref([]);
const suppliers = ref([]);
const paymentMethods = ref([]);
const openGrns = ref([]);
const availableCredits = ref([]);

const isFormVisible = ref(false);
const isEditing = ref(false);
const showConfirmDelete = ref(false);
const grnPaymentToDelete = ref(null);

const selectedSupplier = ref(null);
const isCreating = ref(false);

const form = useForm({
    id: null,
    payment_date: new Date().toISOString().substr(0, 10),
    supplier_id: null,
    payment_method_id: null,
    payment_amount: 0,
    payment_reference: '',
    payment_notes: '',
    grn_applications: [],
    credit_applications: [],
});

const fetchGrnPayments = async () => {
    try {
        console.log('Fetching GRN Payments...');
        const response = await axios.get('/api/grn-payments');
        console.log('GRN Payments response:', response.data);
        grnPayments.value = response.data;
    } catch (error) {
        console.error('Error fetching GRN Payments:', error);
        toast({ title: 'Error', description: 'Failed to fetch GRN Payments.', variant: 'destructive' });
    }
};

const fetchDropdownData = async () => {
    try {
        const [supplierRes, methodRes] = await Promise.all([
            axios.get('/api/suppliers'),
            axios.get('/api/payment-methods')
        ]);
        suppliers.value = supplierRes.data;
        paymentMethods.value = methodRes.data;
    } catch (error) {
        console.log(error);
        toast({ title: 'Error', description: 'Failed to fetch dropdown data.', variant: 'destructive' });
    }
};

onMounted(() => {
    fetchGrnPayments();
    fetchDropdownData();
    
    // Add global escape key handler for dialog cleanup
    const handleEscape = (event) => {
        if (event.key === 'Escape' && grnPaymentToDelete.value) {
            hideDeleteConfirm();
        }
    };
    
    document.addEventListener('keydown', handleEscape);
    
    // Cleanup on unmount
    return () => {
        document.removeEventListener('keydown', handleEscape);
    };
});

const formatCurrency = (value) => {
    return (Number(value) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateString) => new Date(dateString).toLocaleDateString();

const showCreateForm = () => {
    isEditing.value = false;
    form.reset();
    form.payment_date = new Date().toISOString().substr(0, 10);
    form.payment_amount = 0;
    form.payment_reference = '';
    form.payment_notes = '';
    form.grn_applications = [];
    form.credit_applications = [];
    // Set default payment method if available
    if (paymentMethods.value.length > 0) {
        form.payment_method_id = paymentMethods.value[0].id;
    }
    selectedSupplier.value = null;
    openGrns.value = [];
    availableCredits.value = [];
    isFormVisible.value = true;
};

const editGrnPayment = async (grnPaymentId) => {
    isEditing.value = true;
    try {
        const response = await axios.get(`/api/grn-payments/${grnPaymentId}`);
        const data = response.data;
        form.id = data.id;
        form.payment_date = data.payment_date;
        form.supplier_id = data.supplier_id;
        form.payment_method_id = data.payment_method_id;
        form.payment_amount = data.payment_amount;
        form.payment_reference = data.payment_reference;
        form.payment_notes = data.payment_notes;
        form.grn_applications = data.grn_applications || [];
        form.credit_applications = data.credit_applications || [];
        
        // Set supplier and load supplier data first
        selectedSupplier.value = data.supplier_id;
        await onSupplierChange();
        
        // Now load existing GRN applications after GRNs are loaded
        if (data.grn_applications && data.grn_applications.length > 0) {
            for (const application of data.grn_applications) {
                const grn = openGrns.value.find(g => g.id === application.grn_id);
                if (grn) {
                    grn.apply_payment = application.apply_payment || 0;
                    grn.apply_credits = application.apply_credits || 0;
                }
            }
            calculateGrnTotals();
        }
        
        // Load existing credit applications after credits are loaded
        if (data.credit_applications && data.credit_applications.length > 0) {
            for (const creditId of data.credit_applications) {
                const credit = availableCredits.value.find(c => c.id === creditId);
                if (credit) {
                    credit.selected = true;
                }
            }
        }
        
        isFormVisible.value = true;
    } catch (error) {
        toast({ title: 'Error', description: 'Failed to fetch GRN Payment details.', variant: 'destructive' });
    }
};

const hideForm = () => {
    isFormVisible.value = false;
    form.reset();
    selectedSupplier.value = null;
    openGrns.value = [];
    availableCredits.value = [];
};

const saveGrnPayment = async () => {
    const method = isEditing.value ? 'put' : 'post';
    const url = isEditing.value ? `/api/grn-payments/${form.id}` : '/api/grn-payments';

    try {
        let payload;
        
        if (isEditing.value) {
            // For updates, send the same structure as create
            payload = {
                payment_date: form.payment_date,
                payment_method_id: form.payment_method_id,
                payment_amount: parseFloat(form.payment_amount),
                payment_reference: form.payment_reference,
                payment_notes: form.payment_notes,
                grn_applications: openGrns.value
                    .filter(grn => grn.apply_payment > 0 || grn.apply_credits > 0)
                    .map(grn => ({
                        grn_id: grn.id,
                        apply_payment: parseFloat(grn.apply_payment) || 0,
                        apply_credits: parseFloat(grn.apply_credits) || 0
                    })),
                credit_applications: availableCredits.value
                    .filter(credit => credit.selected)
                    .map(credit => credit.id)
            };
        } else {
            // For creates, use the existing createPayment function
            return createPayment();
        }

        await axios[method](url, payload);
        toast({ title: 'Success', description: `GRN Payment ${isEditing.value ? 'updated' : 'created'} successfully.` });
        fetchGrnPayments();
        hideForm();
    } catch (error) {
        const errorMessage = error.response?.data?.message || `Failed to ${isEditing.value ? 'update' : 'create'} GRN Payment.`;
        toast({ title: 'Error', description: errorMessage, variant: 'destructive' });
    }
};

const showDeleteConfirm = (grnPayment) => {
    grnPaymentToDelete.value = grnPayment;
    showConfirmDelete.value = true;
};

const hideDeleteConfirm = () => {
    showConfirmDelete.value = false;
    grnPaymentToDelete.value = null;
};

const confirmDelete = async () => {
    if (!grnPaymentToDelete.value) return;
    
    try {
        await axios.delete(`/api/grn-payments/${grnPaymentToDelete.value.id}`);
        toast({ title: 'Success', description: 'GRN Payment deleted successfully.' });
        fetchGrnPayments();
    } catch (error) {
        toast({ title: 'Error', description: 'Failed to delete GRN Payment.', variant: 'destructive' });
    } finally {
        hideDeleteConfirm();
    }
};

// Supplier change handler
const onSupplierChange = async () => {
    if (!selectedSupplier.value) {
        openGrns.value = [];
        availableCredits.value = [];
        return;
    }
    
    try {
        console.log('onSupplierChange - isEditing:', isEditing.value, 'form.id:', form.id, 'selectedSupplier:', selectedSupplier.value);
        // Load GRNs for the supplier - use all GRNs when editing, open GRNs when creating
        const endpoint = isEditing.value ? 'all-grns' : 'open-grns';
        let url;
        if (isEditing.value && form.id) {
            url = `/api/grn-payments/supplier/${selectedSupplier.value}/${endpoint}/${form.id}`;
        } else {
            url = `/api/grn-payments/supplier/${selectedSupplier.value}/${endpoint}`;
        }
        console.log('Loading GRNs from URL:', url);
        const grnResponse = await axios.get(url);
        console.log('GRN Response:', grnResponse.data);
        openGrns.value = grnResponse.data.map(grn => ({
            ...grn,
            apply_payment: 0,
            apply_credits: 0,
            final_balance: grn.balance_due
        }));

        // Load credits for the supplier - use all credits when editing, available credits when creating
        const creditEndpoint = isEditing.value ? 'all-credits' : 'available-credits';
        let creditUrl;
        if (isEditing.value && form.id) {
            creditUrl = `/api/grn-payments/supplier/${selectedSupplier.value}/${creditEndpoint}/${form.id}`;
        } else {
            creditUrl = `/api/grn-payments/supplier/${selectedSupplier.value}/${creditEndpoint}`;
        }
        console.log('Loading credits from URL:', creditUrl);
        const creditResponse = await axios.get(creditUrl);
        console.log('Credit Response:', creditResponse.data);
        availableCredits.value = creditResponse.data.map(credit => ({
            ...credit,
            selected: false
        }));

        // Auto-apply payment amount to oldest GRNs (only when creating)
        if (!isEditing.value) {
            autoApplyPayment();
        }
    } catch (error) {
        toast({
            title: 'Error',
            description: 'Failed to load supplier data',
            variant: 'destructive',
        });
        console.error(error);
    }
};

const onPaymentAmountChange = () => {
    autoApplyPayment();
};

const autoApplyPayment = () => {
    const paymentAmount = parseFloat(form.payment_amount) || 0;
    let remainingAmount = paymentAmount;

    // Sort GRNs by date (oldest first)
    const sortedGrns = [...openGrns.value].sort((a, b) => new Date(a.grn_date) - new Date(b.grn_date));

    for (const grn of sortedGrns) {
        if (remainingAmount <= 0) break
        
        const balanceDue = parseFloat(grn.balance_due);
        const applyAmount = Math.min(remainingAmount, balanceDue);
        
        grn.apply_payment = applyAmount;
        remainingAmount -= applyAmount;
    }

    calculateGrnTotals();
};

const calculateGrnTotals = () => {
    openGrns.value.forEach(grn => {
        const balanceDue = parseFloat(grn.balance_due);
        const applyPayment = parseFloat(grn.apply_payment) || 0;
        const applyCredits = parseFloat(grn.apply_credits) || 0;
        
        grn.final_balance = Math.max(0, balanceDue - applyPayment - applyCredits);
    });
};

const selectAllCredits = computed({
    get() {
        return availableCredits.value.length > 0 && availableCredits.value.every(credit => credit.selected);
    },
    set(value) {
        availableCredits.value.forEach(credit => {
            credit.selected = value;
        });
    }
});

const createPayment = async () => {
    if (!canCreatePayment.value) return;

    isCreating.value = true;
    
    try {
        const paymentPayload = {
            supplier_id: selectedSupplier.value,
            payment_date: form.payment_date,
            payment_method_id: form.payment_method_id,
            payment_amount: parseFloat(form.payment_amount),
            payment_reference: form.payment_reference,
            payment_notes: form.payment_notes,
            grn_applications: openGrns.value
                .filter(grn => grn.apply_payment > 0 || grn.apply_credits > 0)
                .map(grn => ({
                    grn_id: grn.id,
                    apply_payment: parseFloat(grn.apply_payment) || 0,
                    apply_credits: parseFloat(grn.apply_credits) || 0
                })),
            credit_applications: availableCredits.value
                .filter(credit => credit.selected)
                .map(credit => credit.id)
        };

        const response = await axios.post('/api/grn-payments', paymentPayload);

        if (response.status === 200 || response.status === 201) {
            toast({
                title: 'Success',
                description: 'Payment created successfully',
            });
            hideForm();
            fetchGrnPayments();
        }
    } catch (error) {
        toast({
            title: 'Error',
            description: error.response?.data?.message || 'Failed to create payment',
            variant: 'destructive',
        });
        console.error(error);
    } finally {
        isCreating.value = false;
    }
};

// Computed properties
const totalAppliedPayment = computed(() => {
    return openGrns.value.reduce((sum, grn) => sum + (parseFloat(grn.apply_payment) || 0), 0);
});

const totalCreditValue = computed(() => {
    return availableCredits.value
        .filter(credit => credit.selected)
        .reduce((sum, credit) => sum + parseFloat(credit.total_amount), 0);
});

const totalAppliedCredits = computed(() => {
    return openGrns.value.reduce((sum, grn) => sum + (parseFloat(grn.apply_credits) || 0), 0);
});

const selectedCreditsCount = computed(() => {
    return availableCredits.value.filter(credit => credit.selected).length;
});

const unappliedAmount = computed(() => {
    return (parseFloat(form.payment_amount) || 0) - totalAppliedPayment.value;
});

const canCreatePayment = computed(() => {
    return selectedSupplier.value && 
           form.payment_amount > 0 && 
           form.payment_method_id &&
           (totalAppliedPayment.value > 0 || totalAppliedCredits.value > 0);
});

const getCreditStatusVariant = (status) => {
    switch (status) {
        case 'Open': return 'default';
        case 'Partial': return 'secondary';
        case 'Closed': return 'destructive';
        default: return 'default';
    }
};

const getPaymentStatusVariant = (status) => {
    switch (status) {
        case 'Open': return 'default';
        case 'Partial': return 'secondary';
        case 'Completed': return 'destructive';
        default: return 'default';
    }
};

const isPaymentCompleted = computed(() => form.payment_status === 'Completed');
const isPaymentPartial = computed(() => form.payment_status === 'Partial');

</script>

<template>
    <Head title="GRN Payments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col h-full">
            <!-- GRN Payment List View -->
            <Card v-if="!isFormVisible" class="flex-1">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>GRN Payments</CardTitle>
                        <div class="flex items-center gap-2">
                            <Button @click="showCreateForm">Create New Payment</Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Payment #</TableHead>
                                <TableHead>Supplier</TableHead>
                                <TableHead>Payment Method</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="w-[100px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="payment in grnPayments" :key="payment.id">
                                <TableCell>{{ formatDate(payment.payment_date) }}</TableCell>
                                <TableCell>PAY-{{ payment.id }}</TableCell>
                                <TableCell>{{ payment.supplier?.user?.name }}</TableCell>
                                <TableCell>{{ payment.payment_method?.payment_method_name }}</TableCell>
                                <TableCell>{{ formatCurrency(payment.payment_amount) }}</TableCell>
                                <TableCell>
                                    <Badge 
                                        :variant="payment.payment_status === 'Completed' ? 'destructive' : payment.payment_status === 'Partial' ? 'secondary' : 'default'"
                                        :class="payment.payment_status === 'Partial' ? 'bg-orange-500 text-white hover:bg-orange-500' : payment.payment_status === 'Completed' ? 'hover:bg-destructive' : 'hover:bg-primary'"
                                    >
                                        {{ payment.payment_status }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child><Button variant="ghost" class="h-8 w-8 p-0"><MoreHorizontal class="h-4 w-4" /></Button></DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="editGrnPayment(payment.id)">Edit</DropdownMenuItem>
                                            <DropdownMenuItem @click="showDeleteConfirm(payment)" class="text-destructive focus:text-destructive">Delete</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="grnPayments.length === 0">
                                <TableCell colspan="7" class="h-24 text-center">No GRN payments found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- GRN Payment Create/Edit Form -->
            <div v-else class="flex flex-col gap-4 relative">
                <!-- Bottom Watermarks -->
                <div v-if="isPaymentCompleted" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-10 pointer-events-none">
                    <div class="bg-red-600/95 text-white px-12 py-6 rounded-xl transform -rotate-12 text-3xl font-bold shadow-2xl border-2 border-red-700">
                        COMPLETED
                    </div>
                </div>
                <div v-if="isPaymentPartial" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-10 pointer-events-none">
                    <div class="bg-orange-600/95 text-white px-12 py-6 rounded-xl transform -rotate-12 text-3xl font-bold shadow-2xl border-2 border-orange-700">
                        PARTIAL
                    </div>
                </div>
                <!-- Form Overlay for Completed Payment -->
                <div v-if="isPaymentCompleted" class="absolute inset-0 z-5 bg-gray-900/20 pointer-events-none rounded-lg"></div>
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>{{ isEditing ? 'Edit' : 'Create' }} GRN Payment</CardTitle>
                            <Badge v-if="isPaymentCompleted" variant="destructive" class="text-lg px-4 py-2">
                                {{ form.payment_status }}
                            </Badge>
                            <Badge v-if="isPaymentPartial" variant="secondary" class="text-lg px-4 py-2 bg-orange-500 text-white">
                                {{ form.payment_status }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex flex-col space-y-1.5">
                            <Label>Payment Date</Label>
                            <Input v-model="form.payment_date" type="date" />
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <Label>Supplier</Label>
                            <Select v-model="selectedSupplier" @update:model-value="onSupplierChange">
                                <SelectTrigger><SelectValue placeholder="Select supplier" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.user?.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <Label>Payment Method</Label>
                            <Select v-model="form.payment_method_id">
                                <SelectTrigger><SelectValue placeholder="Select payment method" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="m in paymentMethods" :key="m.id" :value="m.id">{{ m.method_name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <Label>Payment Amount</Label>
                            <Input v-model="form.payment_amount" type="number" step="0.01" placeholder="0.00" @input="onPaymentAmountChange" />
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <Label>Reference</Label>
                            <Input v-model="form.payment_reference" placeholder="Payment reference" />
                        </div>

                        <div class="flex flex-col space-y-1.5 md:col-span-3">
                            <Label>Notes</Label>
                            <Textarea v-model="form.payment_notes" placeholder="Payment notes..." />
                        </div>
                    </CardContent>
                </Card>

                <!-- Open GRNs Table -->
                <Card v-if="selectedSupplier && openGrns.length > 0">
                    <CardHeader><CardTitle>Open GRNs</CardTitle></CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>GRN Date</TableHead>
                                    <TableHead>GRN #</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead class="text-right">Total Amount</TableHead>
                                    <TableHead class="text-right">Balance Due</TableHead>
                                    <TableHead class="text-right">Apply Payment</TableHead>
                                    <TableHead class="text-right">Apply Credits</TableHead>
                                    <TableHead class="text-right">Final Balance</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="grn in openGrns" :key="grn.id">
                                    <TableCell>{{ formatDate(grn.grn_date) }}</TableCell>
                                    <TableCell>{{ grn.id }}</TableCell>
                                    <TableCell>{{ grn.location?.location_name }}</TableCell>
                                    <TableCell class="text-right">{{ formatCurrency(grn.total_amount) }}</TableCell>
                                    <TableCell class="text-right">{{ formatCurrency(grn.balance_due) }}</TableCell>
                                    <TableCell class="text-right">
                                        <Input 
                                            type="number" 
                                            step="0.01" 
                                            v-model="grn.apply_payment"
                                            @input="calculateGrnTotals"
                                            class="w-24 text-right"
                                            :max="grn.balance_due"
                                            min="0"
                                        />
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Input 
                                            type="number" 
                                            step="0.01" 
                                            v-model="grn.apply_credits"
                                            @input="calculateGrnTotals"
                                            class="w-24 text-right"
                                            :max="grn.balance_due"
                                            min="0"
                                        />
                                    </TableCell>
                                    <TableCell class="text-right font-medium">
                                        {{ formatCurrency(grn.final_balance) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <!-- Available GRN Credits -->
                <Card v-if="selectedSupplier && availableCredits.length > 0">
                    <CardHeader><CardTitle>Available GRN Credits</CardTitle></CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-2">
                                <Checkbox 
                                    id="select_all_credits" 
                                    v-model="selectAllCredits"
                                />
                                <Label for="select_all_credits">Select All Credits</Label>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <Card v-for="credit in availableCredits" :key="credit.id" class="p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center space-x-2">
                                            <Checkbox 
                                                :id="`credit_${credit.id}`" 
                                                v-model="credit.selected"
                                            />
                                            <div>
                                                <p class="font-medium">Credit #{{ credit.id }}</p>
                                                <p class="text-sm text-gray-600">{{ formatDate(credit.grn_credit_date) }}</p>
                                                <p class="text-sm text-gray-600">{{ formatCurrency(credit.total_amount) }}</p>
                                            </div>
                                        </div>
                                        <Badge :variant="getCreditStatusVariant(credit.grn_credit_status)">
                                            {{ credit.grn_credit_status }}
                                        </Badge>
                                    </div>
                                </Card>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payment Summary -->
                <Card v-if="selectedSupplier">
                    <CardHeader><CardTitle>Payment Summary</CardTitle></CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h3 class="font-semibold">Payment Details</h3>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span>Payment Amount:</span>
                                        <span class="font-medium">{{ formatCurrency(form.payment_amount || 0) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Applied to GRNs:</span>
                                        <span class="font-medium">{{ formatCurrency(totalAppliedPayment) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Unapplied Amount:</span>
                                        <span class="font-medium" :class="unappliedAmount < 0 ? 'text-red-600' : ''">
                                            {{ formatCurrency(unappliedAmount) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <h3 class="font-semibold">Credit Details</h3>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span>Selected Credits:</span>
                                        <span class="font-medium">{{ selectedCreditsCount }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Total Credit Value:</span>
                                        <span class="font-medium">{{ formatCurrency(totalCreditValue) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Applied Credits:</span>
                                        <span class="font-medium">{{ formatCurrency(totalAppliedCredits) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-4 border-t">
                            <div class="flex justify-between text-lg font-semibold">
                                <span>Total Applied:</span>
                                <span>{{ formatCurrency(totalAppliedPayment + totalAppliedCredits) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-between items-center p-4 rounded-lg bg-card border">
                    <div><CardTitle>Payment Summary</CardTitle></div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="hideForm">Cancel</Button>
                        <Button @click="saveGrnPayment" :disabled="!canCreatePayment" :loading="isCreating">{{ isEditing ? 'Update Payment' : 'Create Payment' }}</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Delete Confirmation Modal -->
        <div v-if="showConfirmDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm">
            <div class="bg-background border border-border rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-foreground">Delete GRN Payment</h3>
                    <button @click="hideDeleteConfirm" class="text-muted-foreground hover:text-foreground transition-colors">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <p class="text-muted-foreground mb-6">
                    Are you sure you want to delete GRN Payment <strong class="text-foreground">PAY-{{ grnPaymentToDelete?.id }}</strong>? 
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