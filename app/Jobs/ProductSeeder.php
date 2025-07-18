<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\TenantModels\Product;
use App\Models\TenantModels\ProductType;
use App\Models\TenantModels\ProductCategory;
use App\Models\TenantModels\Account;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductSeeder implements ShouldQueue
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
        $physicalProductType = ProductType::where('type_name', 'Physical Product')->first();
        $serviceType = ProductType::where('type_name', 'Service')->first();
        $finishedGoodsType = ProductType::where('type_name', 'Finished Goods')->first();
        $consumablesType = ProductType::where('type_name', 'Consumables')->first();
        
        $electronicsCategory = ProductCategory::where('category_name', 'Electronics')->first();
        $clothingCategory = ProductCategory::where('category_name', 'Clothing & Apparel')->first();
        $foodCategory = ProductCategory::where('category_name', 'Food & Beverages')->first();
        $homeCategory = ProductCategory::where('category_name', 'Home & Garden')->first();
        $officeCategory = ProductCategory::where('category_name', 'Office Supplies')->first();

        // Get accounts
        $salesRevenueAccount = Account::where('account_name', 'Sales Revenue')->first();
        $serviceRevenueAccount = Account::where('account_name', 'Service Revenue')->first();
        $costOfGoodsSoldAccount = Account::where('account_name', 'Cost of Goods Sold')->first();
        $costOfServicesAccount = Account::where('account_name', 'Cost of Services')->first();
        $inventoryAccount = Account::where('account_name', 'Inventory')->first();

        // Create sample products
        $this->createProduct('PROD-001', 'Laptop Computer', 'High-performance laptop for business use', 'Inventory', $physicalProductType, $electronicsCategory, 45000.00, 65000.00, $salesRevenueAccount, $costOfGoodsSoldAccount, $inventoryAccount);
        $this->createProduct('PROD-002', 'Consulting Service', 'Professional business consulting services', 'Service', $serviceType, null, 0.00, 5000.00, $serviceRevenueAccount, $costOfServicesAccount, null);
        $this->createProduct('PROD-003', 'Cotton T-Shirt', 'Comfortable cotton t-shirt in various sizes', 'Inventory', $finishedGoodsType, $clothingCategory, 800.00, 1500.00, $salesRevenueAccount, $costOfGoodsSoldAccount, $inventoryAccount);
        $this->createProduct('PROD-004', 'Rice 5kg Bag', 'Premium quality rice in 5kg packaging', 'Inventory', $finishedGoodsType, $foodCategory, 1200.00, 1800.00, $salesRevenueAccount, $costOfGoodsSoldAccount, $inventoryAccount);
        $this->createProduct('PROD-005', 'Office Chair', 'Ergonomic office chair with adjustable features', 'Inventory', $physicalProductType, $officeCategory, 15000.00, 25000.00, $salesRevenueAccount, $costOfGoodsSoldAccount, $inventoryAccount);
        $this->createProduct('PROD-006', 'Printer Paper A4', 'High-quality A4 printer paper, 500 sheets', 'Inventory', $consumablesType, $officeCategory, 300.00, 500.00, $salesRevenueAccount, $costOfGoodsSoldAccount, $inventoryAccount);
        $this->createProduct('PROD-007', 'Garden Tool Set', 'Complete set of essential garden tools', 'Inventory', $physicalProductType, $homeCategory, 5000.00, 8000.00, $salesRevenueAccount, $costOfGoodsSoldAccount, $inventoryAccount);
        $this->createProduct('PROD-008', 'Mobile Phone', 'Latest smartphone with advanced features', 'Inventory', $physicalProductType, $electronicsCategory, 35000.00, 55000.00, $salesRevenueAccount, $costOfGoodsSoldAccount, $inventoryAccount);
        $this->createProduct('PROD-009', 'Training Workshop', 'Professional training and development workshop', 'Service', $serviceType, null, 0.00, 15000.00, $serviceRevenueAccount, $costOfServicesAccount, null);
        $this->createProduct('PROD-010', 'Coffee Beans 1kg', 'Premium coffee beans, 1kg package', 'Inventory', $finishedGoodsType, $foodCategory, 2500.00, 3500.00, $salesRevenueAccount, $costOfGoodsSoldAccount, $inventoryAccount);
    }

    private function createProduct($code, $name, $description, $inventoryType, $productType, $productCategory, $cost, $price, $salesAccount, $expenseAccount, $inventoryAccount)
    {
        Product::updateOrCreate(
            ['product_name' => $name, 'inventory_type' => $inventoryType],
            [
                'product_description' => $description,
                'product_type_id' => $productType ? $productType->id : null,
                'product_category_id' => $productCategory ? $productCategory->id : null,
                'cost' => $cost,
                'price' => $price,
                'sales_account_id' => $salesAccount ? $salesAccount->id : 1,
                'expense_account_id' => $expenseAccount ? $expenseAccount->id : 1,
                'inventory_account_id' => $inventoryAccount ? $inventoryAccount->id : null,
                'created_by' => 1,
            ]
        );
    }
} 