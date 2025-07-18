<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\TenantModels\PaymentMethod;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentMethodSeeder implements ShouldQueue
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
        PaymentMethod::updateOrCreate(
            ['method_name' => 'Cash'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1, // Admin user
                'approved_by' => 1,
            ]
        );

        PaymentMethod::updateOrCreate(
            ['method_name' => 'Bank Transfer'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        PaymentMethod::updateOrCreate(
            ['method_name' => 'Check'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        PaymentMethod::updateOrCreate(
            ['method_name' => 'Credit Card'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );
    }
} 