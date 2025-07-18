<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\TenantModels\SupplierType;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SupplierTypeSeeder implements ShouldQueue
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
        // Main supplier types
        SupplierType::updateOrCreate(
            ['type_name' => 'Manufacturer'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        SupplierType::updateOrCreate(
            ['type_name' => 'Distributor'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        SupplierType::updateOrCreate(
            ['type_name' => 'Importer'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        SupplierType::updateOrCreate(
            ['type_name' => 'Local Supplier'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        SupplierType::updateOrCreate(
            ['type_name' => 'Service Provider'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        SupplierType::updateOrCreate(
            ['type_name' => 'Equipment Supplier'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        SupplierType::updateOrCreate(
            ['type_name' => 'Raw Material Supplier'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        SupplierType::updateOrCreate(
            ['type_name' => 'Packaging Supplier'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );
    }
} 