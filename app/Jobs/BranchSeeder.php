<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\TenantModels\Branch;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BranchSeeder implements ShouldQueue
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
        // Main branches
        Branch::updateOrCreate(
            ['branch_name' => 'Head Office - Colombo'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Colombo Central Branch'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Colombo South Branch'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Colombo North Branch'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Regional branches
        Branch::updateOrCreate(
            ['branch_name' => 'Kandy Regional Branch'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Galle Regional Branch'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Jaffna Regional Branch'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Anuradhapura Regional Branch'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Matara Regional Branch'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Kurunegala Regional Branch'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Specialized branches
        Branch::updateOrCreate(
            ['branch_name' => 'Export Division'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Import Division'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Wholesale Division'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Retail Division'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Distribution Center'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        Branch::updateOrCreate(
            ['branch_name' => 'Service Center'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );
    }
} 