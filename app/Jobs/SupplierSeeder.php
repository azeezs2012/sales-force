<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\User;
use App\Models\TenantModels\Supplier;
use App\Models\TenantModels\SupplierType;
use App\Models\TenantModels\PaymentMethod;
use App\Models\TenantModels\PaymentTerm;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SupplierSeeder implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $tenant;

    /**
     * Create a new job instance.
     */
    public function __construct($tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get required data
        $manufacturerType = SupplierType::where('type_name', 'Manufacturer')->first();
        $distributorType = SupplierType::where('type_name', 'Distributor')->first();
        $importerType = SupplierType::where('type_name', 'Importer')->first();
        $localSupplierType = SupplierType::where('type_name', 'Local Supplier')->first();
        $serviceProviderType = SupplierType::where('type_name', 'Service Provider')->first();
        $equipmentSupplierType = SupplierType::where('type_name', 'Equipment Supplier')->first();
        $rawMaterialType = SupplierType::where('type_name', 'Raw Material Supplier')->first();
        $packagingType = SupplierType::where('type_name', 'Packaging Supplier')->first();
        
        $cashPayment = PaymentMethod::where('method_name', 'Cash')->first();
        $bankTransfer = PaymentMethod::where('method_name', 'Bank Transfer')->first();
        $net30Term = PaymentTerm::where('payment_term_name', 'Net 30')->first();
        $net45Term = PaymentTerm::where('payment_term_name', 'Net 45')->first();
        $net60Term = PaymentTerm::where('payment_term_name', 'Net 60')->first();

        // Create sample suppliers
        $this->createSupplier('SUP-001', 'Lanka Trading Co.', 'info@lankatrading.lk', '+94 11 2345678', $distributorType, $bankTransfer, $net30Term);
        $this->createSupplier('SUP-002', 'Ceylon Imports Ltd.', 'sales@ceylonimports.lk', '+94 11 3456789', $importerType, $bankTransfer, $net45Term);
        $this->createSupplier('SUP-003', 'Kandy Manufacturing', 'info@kandymanufacturing.lk', '+94 81 2345678', $manufacturerType, $bankTransfer, $net30Term);
        $this->createSupplier('SUP-004', 'Galle Equipment Suppliers', 'sales@galleequipment.lk', '+94 91 3456789', $equipmentSupplierType, $bankTransfer, $net60Term);
        $this->createSupplier('SUP-005', 'Jaffna Raw Materials', 'info@jaffnarawmaterials.lk', '+94 21 2345678', $rawMaterialType, $bankTransfer, $net30Term);
        $this->createSupplier('SUP-006', 'Matara Packaging Solutions', 'sales@matarapackaging.lk', '+94 41 3456789', $packagingType, $bankTransfer, $net45Term);
        $this->createSupplier('SUP-007', 'Anuradhapura Services', 'info@anuradhapuraservices.lk', '+94 25 2345678', $serviceProviderType, $bankTransfer, $net30Term);
        $this->createSupplier('SUP-008', 'Kurunegala Local Suppliers', 'sales@kurunegalalocal.lk', '+94 37 3456789', $localSupplierType, $cashPayment, $net30Term);
    }

    private function createSupplier($code, $name, $email, $phone, $supplierType, $paymentMethod, $paymentTerm)
    {
        // Create user first
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt('password'),
                'is_admin' => false,
                'role' => 'supplier',
            ]
        );

        // Create supplier
        Supplier::updateOrCreate(
            ['supplier_code' => $code],
            [
                'phone_no' => $phone,
                'default_payment_method' => $paymentMethod ? $paymentMethod->id : null,
                'default_payment_term' => $paymentTerm ? $paymentTerm->id : null,
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
                'user_id' => $user->id,
            ]
        );
    }
} 