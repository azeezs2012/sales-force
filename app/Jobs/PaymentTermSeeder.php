<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\TenantModels\PaymentTerm;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentTermSeeder implements ShouldQueue
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
        PaymentTerm::updateOrCreate(
            ['payment_term_name' => 'Immediate'],
            [
                'duration_count' => 0,
                'duration_unit' => 'days',
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        PaymentTerm::updateOrCreate(
            ['payment_term_name' => 'Net 30'],
            [
                'duration_count' => 30,
                'duration_unit' => 'days',
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        PaymentTerm::updateOrCreate(
            ['payment_term_name' => 'Net 60'],
            [
                'duration_count' => 60,
                'duration_unit' => 'days',
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        PaymentTerm::updateOrCreate(
            ['payment_term_name' => 'Net 90'],
            [
                'duration_count' => 90,
                'duration_unit' => 'days',
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );
    }
} 