<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\TenantModels\ProductCategory;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductCategorySeeder implements ShouldQueue
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
        // Main product categories
        ProductCategory::updateOrCreate(
            ['category_name' => 'Electronics'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductCategory::updateOrCreate(
            ['category_name' => 'Clothing & Apparel'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductCategory::updateOrCreate(
            ['category_name' => 'Food & Beverages'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductCategory::updateOrCreate(
            ['category_name' => 'Home & Garden'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductCategory::updateOrCreate(
            ['category_name' => 'Automotive'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductCategory::updateOrCreate(
            ['category_name' => 'Health & Beauty'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductCategory::updateOrCreate(
            ['category_name' => 'Sports & Recreation'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductCategory::updateOrCreate(
            ['category_name' => 'Books & Media'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductCategory::updateOrCreate(
            ['category_name' => 'Office Supplies'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductCategory::updateOrCreate(
            ['category_name' => 'Industrial & Construction'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );
    }
} 