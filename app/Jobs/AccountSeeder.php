<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\TenantModels\Account;
use App\Models\TenantModels\AccountType;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AccountSeeder implements ShouldQueue
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
        // Get account types
        $cashType = AccountType::where('account_type_name', 'Cash')->first();
        $bankType = AccountType::where('account_type_name', 'Bank')->first();
        $accountsReceivableType = AccountType::where('account_type_name', 'Accounts Receivable')->first();
        $otherCurrentAssetsType = AccountType::where('account_type_name', 'Other Current Assets')->first();
        $equityType = AccountType::where('account_type_name', 'Equity')->first();
        $accountsPayableType = AccountType::where('account_type_name', 'Accounts Payable')->first();
        $revenueType = AccountType::where('account_type_name', 'Revenue')->first();
        $costOfGoodsSoldType = AccountType::where('account_type_name', 'Cost of Goods Sold')->first();
        $expenseType = AccountType::where('account_type_name', 'Expense')->first();

        // Create main accounts
        $this->createAccount('Cash', 'Cash on hand and in bank accounts', $cashType, null);
        $this->createAccount('Bank Account', 'Main business bank account', $bankType, null);
        $this->createAccount('Accounts Receivable', 'Money owed by customers', $accountsReceivableType, null);
        $this->createAccount('Inventory', 'Current inventory stock', $otherCurrentAssetsType, null);
        $this->createAccount('Retained Earnings', 'Accumulated profits', $equityType, null);
        $this->createAccount('Accounts Payable', 'Money owed to suppliers', $accountsPayableType, null);
        
        // Revenue accounts
        $this->createAccount('Sales Revenue', 'Revenue from product sales', $revenueType, null);
        $this->createAccount('Service Revenue', 'Revenue from service sales', $revenueType, null);
        
        // Cost of Goods Sold accounts
        $this->createAccount('Cost of Goods Sold', 'Direct costs of products sold', $costOfGoodsSoldType, null);
        $this->createAccount('Cost of Services', 'Direct costs of services provided', $costOfGoodsSoldType, null);
        
        // Expense accounts
        $this->createAccount('Operating Expenses', 'General operating expenses', $expenseType, null);
        $this->createAccount('Administrative Expenses', 'Administrative and office expenses', $expenseType, null);
        $this->createAccount('Marketing Expenses', 'Advertising and marketing costs', $expenseType, null);
        $this->createAccount('Utilities', 'Electricity, water, and other utilities', $expenseType, null);
        $this->createAccount('Rent Expense', 'Office and warehouse rent', $expenseType, null);
        $this->createAccount('Salaries and Wages', 'Employee salaries and wages', $expenseType, null);
    }

    private function createAccount($name, $description, $accountType, $parentAccount)
    {
        Account::updateOrCreate(
            ['account_name' => $name, 'account_type_id' => $accountType->id],
            [
                'account_description' => $description,
                'parent_id' => $parentAccount ? $parentAccount->id : null,
                'created_by' => 1,
            ]
        );
    }
} 