<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\User;
use App\Models\TenantModels\Customer;
use App\Models\TenantModels\CustomerType;
use App\Models\TenantModels\PaymentMethod;
use App\Models\TenantModels\PaymentTerm;
use App\Models\TenantModels\SalesRep;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CustomerSeeder implements ShouldQueue
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
        $retailType = CustomerType::where('type_name', 'Retail Customer')->first();
        $wholesaleType = CustomerType::where('type_name', 'Wholesale Customer')->first();
        $corporateType = CustomerType::where('type_name', 'Corporate Customer')->first();
        $cashPayment = PaymentMethod::where('method_name', 'Cash')->first();
        $bankTransfer = PaymentMethod::where('method_name', 'Bank Transfer')->first();
        $net30Term = PaymentTerm::where('payment_term_name', 'Net 30')->first();
        $immediateTerm = PaymentTerm::where('payment_term_name', 'Immediate')->first();
        $salesRep = SalesRep::first(); // Get first sales rep if available

        // Create sample customers
        $this->createCustomer('CUS-001', 'ABC Retail Store', 'retail@abcstore.com', '0771234567', 50000, $retailType, $cashPayment, $immediateTerm, $salesRep);
        $this->createCustomer('CUS-002', 'XYZ Wholesale Ltd', 'sales@xyzwholesale.com', '0772345678', 200000, $wholesaleType, $bankTransfer, $net30Term, $salesRep);
        $this->createCustomer('CUS-003', 'Colombo Trading Co', 'info@colombotrading.com', '0773456789', 150000, $corporateType, $bankTransfer, $net30Term, $salesRep);
        $this->createCustomer('CUS-004', 'Kandy Merchants', 'contact@kandymerchants.com', '0811234567', 75000, $retailType, $cashPayment, $immediateTerm, $salesRep);
        $this->createCustomer('CUS-005', 'Galle Exporters', 'export@galleexporters.com', '0912345678', 300000, $corporateType, $bankTransfer, $net30Term, $salesRep);
        $this->createCustomer('CUS-006', 'Jaffna Traders', 'trade@jaffnatraders.com', '0211234567', 100000, $wholesaleType, $bankTransfer, $net30Term, $salesRep);
        $this->createCustomer('CUS-007', 'Matara Retail', 'sales@matararetail.com', '0412345678', 25000, $retailType, $cashPayment, $immediateTerm, $salesRep);
        $this->createCustomer('CUS-008', 'Anuradhapura Corp', 'corp@anuradhapuracorp.com', '0251234567', 180000, $corporateType, $bankTransfer, $net30Term, $salesRep);
        $this->createCustomer('CUS-009', 'Kurunegala Traders', 'info@kurunegalatraders.com', '0371234567', 120000, $wholesaleType, $bankTransfer, $net30Term, $salesRep);
        $this->createCustomer('CUS-010', 'Fort Business Center', 'business@fortcenter.com', '0111234567', 500000, $corporateType, $bankTransfer, $net30Term, $salesRep);
    }

    private function createCustomer($code, $name, $email, $phone, $creditLimit, $customerType, $paymentMethod, $paymentTerm, $salesRep)
    {
        // Create user first
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt('password'),
                'is_admin' => false,
                'role' => 'customer',
            ]
        );

        // Create customer
        Customer::updateOrCreate(
            ['customer_code' => $code],
            [
                'credit_limit' => $creditLimit,
                'phone_no' => $phone,
                'default_payment_method' => $paymentMethod ? $paymentMethod->id : null,
                'default_payment_term' => $paymentTerm ? $paymentTerm->id : null,
                'default_sales_rep' => $salesRep ? $salesRep->id : null,
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
                'user_id' => $user->id,
            ]
        );
    }
} 