<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Contracts\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MigrateTenantDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle()
    {
        tenancy()->initialize($this->tenant);

        Artisan::call('tenants:migrate', [
            '--tenants' => [$this->tenant->id],
        ]);

        \Log::info("Migrations run for tenant ID: {$this->tenant->id}");
    }
}