<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;




foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
            Route::prefix('entity')->group(function () {
                Route::get('{id}', function ($id) {
                    return response()->json(['message' => 'Tenant ' . $id . ' retrieved successfully']);
                });
                Route::post('{id}/migrate', [TenantController::class, 'migrate']);
                Route::post('{id}/flushdb', [TenantController::class, 'flushdb']);
                Route::post('{id}/rollback', [TenantController::class, 'rollback']);
                Route::delete('{id}/delete', [TenantController::class, 'delete']);
            });
        });
    });
}

