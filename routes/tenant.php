<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\TenantControllers\AuthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Inertia\Inertia;
/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', [AuthController::class, 'loginPage'])
    ->name('tenant.login');
    Route::get('dashboard', function () {
        return Inertia::render('tenant/Dashboard');
    })->middleware(['auth', 'verified'])->name('tenant.dashboard');
    Route::prefix('api')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });
});