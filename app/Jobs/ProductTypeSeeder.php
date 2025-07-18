<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\TenantModels\ProductType;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductTypeSeeder implements ShouldQueue
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
        // Main product types
        ProductType::updateOrCreate(
            ['type_name' => 'Physical Product'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductType::updateOrCreate(
            ['type_name' => 'Digital Product'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductType::updateOrCreate(
            ['type_name' => 'Service'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductType::updateOrCreate(
            ['type_name' => 'Raw Material'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductType::updateOrCreate(
            ['type_name' => 'Finished Goods'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductType::updateOrCreate(
            ['type_name' => 'Consumables'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductType::updateOrCreate(
            ['type_name' => 'Equipment'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        ProductType::updateOrCreate(
            ['type_name' => 'Software'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );
    }
} 