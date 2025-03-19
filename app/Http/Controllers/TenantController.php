<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use Inertia\Inertia;
class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::get(); // Fetch all tenants

        $tenants = $tenants->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'domain' => $tenant->domains->first()->domain,
                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at
            ];
        });
        
        return Inertia::render('Entity', ['tenants' => $tenants]);
    }

    public function migrate($id)
    {
        $tenant = Tenant::findOrFail($id);
        $this->setTenantConnection($tenant);

        \Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => '/database/migrations/tenant',
            '--force' => true,
        ]);

        return response()->json(['message' => 'Migration completed for tenant ' . $id]);
    }

    public function flushdb($id)
    {
        $tenant = Tenant::findOrFail($id);
        $this->setTenantConnection($tenant);

        // Drop all tables
        \DB::connection('tenant')->getSchemaBuilder()->dropAllTables();

        // Run migrations
        \Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => '/database/migrations/tenant',
            '--force' => true,
        ]);

        return response()->json(['message' => 'Database flushed and migrations run fresh for tenant ' . $id]);
    }

    public function rollback($id)
    {
        $tenant = Tenant::findOrFail($id);
        $this->setTenantConnection($tenant);

        \Artisan::call('migrate:rollback', [
            '--database' => 'tenant',
            '--path' => '/database/migrations/tenant',
            '--force' => true,
        ]);

        return response()->json(['message' => 'Rollback completed for tenant ' . $id]);
    }

    public function delete($id)
    {
        $tenant = Tenant::findOrFail($id);
        $this->setTenantConnection($tenant);

        // Optionally, drop the tenant's database
        \DB::connection('tenant')->getSchemaBuilder()->dropAllTables();

        $tenant->delete();

        return response()->json(['message' => 'Tenant deleted']);
    }

    protected function setTenantConnection($tenant)
    {
        config(['database.connections.tenant' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $tenant->database_name,
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]]);

        \DB::setDefaultConnection('tenant');
    }
}
