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
}
