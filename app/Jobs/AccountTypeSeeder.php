<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\TenantModels\AccountType;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AccountTypeSeeder implements ShouldQueue
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
        AccountType::updateOrCreate(['account_type_name' => 'Cash'], ['bs' => true, 'pl' => false, 'display_order' => 1]);
        AccountType::updateOrCreate(['account_type_name' => 'Bank'], ['bs' => true, 'pl' => false, 'display_order' => 2]);
        AccountType::updateOrCreate(['account_type_name' => 'Accounts Receivable'], ['bs' => true, 'pl' => false, 'display_order' => 3]);
        AccountType::updateOrCreate(['account_type_name' => 'Other Current Assets'], ['bs' => true, 'pl' => false, 'display_order' => 4]);
        AccountType::updateOrCreate(['account_type_name' => 'Other Assets'], ['bs' => true, 'pl' => false, 'display_order' => 5]);
        AccountType::updateOrCreate(['account_type_name' => 'Equity'], ['bs' => true, 'pl' => false, 'display_order' => 6]);
        AccountType::updateOrCreate(['account_type_name' => 'Accounts Payable'], ['bs' => true, 'pl' => false, 'display_order' => 7]);
        AccountType::updateOrCreate(['account_type_name' => 'Other Current Liabilities'], ['bs' => true, 'pl' => false, 'display_order' => 8]);
        AccountType::updateOrCreate(['account_type_name' => 'Other Liabilities'], ['bs' => true, 'pl' => false, 'display_order' => 9]);
        AccountType::updateOrCreate(['account_type_name' => 'Revenue'], ['bs' => false, 'pl' => true, 'display_order' => 10]);
        AccountType::updateOrCreate(['account_type_name' => 'Cost of Goods Sold'], ['bs' => false, 'pl' => true, 'display_order' => 11]);
        AccountType::updateOrCreate(['account_type_name' => 'Expense'], ['bs' => false, 'pl' => true, 'display_order' => 12]);
        AccountType::updateOrCreate(['account_type_name' => 'Other Revenue'], ['bs' => false, 'pl' => true, 'display_order' => 13]);
        AccountType::updateOrCreate(['account_type_name' => 'Other Expense'], ['bs' => false, 'pl' => true, 'display_order' => 14]);
    }
}
