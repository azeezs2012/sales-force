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
        set_time_limit(300);

        $tenant = Tenant::findOrFail($id);

        \Artisan::call('tenants:migrate', [
            '--tenants' => $tenant->id,
            '--force' => true,
        ]);

        return response()->json(['message' => 'Migration completed for tenant ' . $tenant->id]);
    }

    public function flushdb($id)
    {
        $tenant = Tenant::findOrFail($id);

        \Artisan::call('tenants:migrate-fresh', [
            '--tenants' => $tenant->id,
        ]);

        return response()->json(['message' => 'Database flushed and migrations run fresh for tenant ' . $id]);
    }

    public function rollback($id)
    {
        $tenant = Tenant::findOrFail($id);
        
        \Artisan::call('tenants:rollback', [
            '--tenants' => $tenant->id,
        ]);

        return response()->json(['message' => 'Rollback completed for tenant ' . $id]);
    }

    public function delete($id)
    {
        $tenant = Tenant::findOrFail($id);

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

    public function create(Request $request)
    {
        // Check if tenant with same ID already exists
        if (Tenant::where('id', $request->id)->exists()) {
            return response()->json([
                'message' => 'Tenant with this ID already exists',
                'error' => 'duplicate_id'
            ], 422);
        }

        $tenant = Tenant::create($request->all());
        $tenant->domains()->create([
            'domain' => $request->id . '.' . config('tenancy.central_domains')[0],
        ]);
        return response()->json(['message' => 'Tenant created', 'tenant' => $tenant]);
    }

    public function all()
    {
        $tenants = Tenant::get();
        return response()->json(['tenants' => $tenants]);
    }
}
