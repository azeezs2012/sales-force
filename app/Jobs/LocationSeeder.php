<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Models\TenantModels\Location;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LocationSeeder implements ShouldQueue
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
        // Colombo - Main business district
        Location::updateOrCreate(
            ['location_name' => 'Colombo 01 - Fort'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Colombo 02 - Slave Island
        Location::updateOrCreate(
            ['location_name' => 'Colombo 02 - Slave Island'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Colombo 03 - Kollupitiya
        Location::updateOrCreate(
            ['location_name' => 'Colombo 03 - Kollupitiya'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Colombo 04 - Bambalapitiya
        Location::updateOrCreate(
            ['location_name' => 'Colombo 04 - Bambalapitiya'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Kandy
        Location::updateOrCreate(
            ['location_name' => 'Kandy City'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Galle
        Location::updateOrCreate(
            ['location_name' => 'Galle City'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Jaffna
        Location::updateOrCreate(
            ['location_name' => 'Jaffna City'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Anuradhapura
        Location::updateOrCreate(
            ['location_name' => 'Anuradhapura City'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Matara
        Location::updateOrCreate(
            ['location_name' => 'Matara City'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );

        // Kurunegala
        Location::updateOrCreate(
            ['location_name' => 'Kurunegala City'],
            [
                'active' => true,
                'approved' => true,
                'created_by' => 1,
                'approved_by' => 1,
            ]
        );
    }
} 