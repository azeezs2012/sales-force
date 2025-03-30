<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\TenantControllers\AuthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\TenantControllers\UserController;
use App\Http\Controllers\TenantControllers\BranchController;
use App\Http\Controllers\TenantControllers\SalesRepController;
use App\Http\Controllers\TenantControllers\LocationController;
use App\Http\Controllers\TenantControllers\CustomerTypeController;
use App\Http\Controllers\TenantControllers\SupplierTypeController;
use App\Http\Controllers\TenantControllers\PaymentMethodController;
use App\Http\Controllers\TenantControllers\ProductTypeController;
use App\Http\Controllers\TenantControllers\ProductCategoryController;
use App\Http\Controllers\TenantControllers\PaymentTermController;
use App\Http\Controllers\TenantControllers\CustomerController;
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

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('dashboard', function () {
            return Inertia::render('tenant/Dashboard');
        })->name('tenant.dashboard');

        Route::get('users', function () {
            return Inertia::render('tenant/lists/Users');
        })->name('tenant.list.users');

        Route::get('branches', function () {
            return Inertia::render('tenant/lists/Branches');
        })->name('tenant.list.branches');

        Route::get('sales-reps', function () {
            return Inertia::render('tenant/lists/SalesReps');
        })->name('tenant.list.sales-reps');

        Route::get('locations', function () {
            return Inertia::render('tenant/lists/Locations');
        })->name('tenant.list.locations');

        Route::get('customer-types', function () {
            return Inertia::render('tenant/lists/CustomerTypes');
        })->name('tenant.list.customer-types');

        Route::get('supplier-types', function () {
            return Inertia::render('tenant/lists/SupplierTypes');
        })->name('tenant.list.supplier-types');

        Route::get('payment-methods', function () {
            return Inertia::render('tenant/lists/PaymentMethods');
        })->name('tenant.list.payment-methods');

        Route::get('product-types', function () {
            return Inertia::render('tenant/lists/ProductTypes');
        })->name('tenant.list.product-types');

        Route::get('product-categories', function () {
            return Inertia::render('tenant/lists/ProductCategories');
        })->name('tenant.list.product-categories');

        Route::get('payment-terms', function () {
            return Inertia::render('tenant/lists/PaymentTerms');
        })->name('tenant.list.payment-terms');

        Route::get('customers', function () {
            return Inertia::render('tenant/lists/Customers');
        })->name('tenant.list.customers');
    });

    Route::prefix('api')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::get('/branches', [BranchController::class, 'index']);
        Route::post('/branches', [BranchController::class, 'store']);
        Route::get('/branches/{id}', [BranchController::class, 'show']);
        Route::put('/branches/{id}', [BranchController::class, 'update']);
        Route::delete('/branches/{id}', [BranchController::class, 'destroy']);
        Route::get('/sales-reps', [SalesRepController::class, 'index']);
        Route::post('/sales-reps', [SalesRepController::class, 'store']);
        Route::get('/sales-reps/{id}', [SalesRepController::class, 'show']);
        Route::put('/sales-reps/{id}', [SalesRepController::class, 'update']);
        Route::delete('/sales-reps/{id}', [SalesRepController::class, 'destroy']);
        Route::get('/locations', [LocationController::class, 'index']);
        Route::post('/locations', [LocationController::class, 'store']);
        Route::get('/locations/{id}', [LocationController::class, 'show']);
        Route::put('/locations/{id}', [LocationController::class, 'update']);
        Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
        Route::get('/customer-types', [CustomerTypeController::class, 'index']);
        Route::post('/customer-types', [CustomerTypeController::class, 'store']);
        Route::get('/customer-types/{id}', [CustomerTypeController::class, 'show']);
        Route::put('/customer-types/{id}', [CustomerTypeController::class, 'update']);
        Route::delete('/customer-types/{id}', [CustomerTypeController::class, 'destroy']);
        Route::get('/supplier-types', [SupplierTypeController::class, 'index']);
        Route::post('/supplier-types', [SupplierTypeController::class, 'store']);
        Route::get('/supplier-types/{id}', [SupplierTypeController::class, 'show']);
        Route::put('/supplier-types/{id}', [SupplierTypeController::class, 'update']);
        Route::delete('/supplier-types/{id}', [SupplierTypeController::class, 'destroy']);
        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
        Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
        Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'show']);
        Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update']);
        Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);
        Route::get('/product-types', [ProductTypeController::class, 'index']);
        Route::post('/product-types', [ProductTypeController::class, 'store']);
        Route::get('/product-types/{id}', [ProductTypeController::class, 'show']);
        Route::put('/product-types/{id}', [ProductTypeController::class, 'update']);
        Route::delete('/product-types/{id}', [ProductTypeController::class, 'destroy']);
        Route::get('/product-categories', [ProductCategoryController::class, 'index']);
        Route::post('/product-categories', [ProductCategoryController::class, 'store']);
        Route::get('/product-categories/{id}', [ProductCategoryController::class, 'show']);
        Route::put('/product-categories/{id}', [ProductCategoryController::class, 'update']);
        Route::delete('/product-categories/{id}', [ProductCategoryController::class, 'destroy']);
        Route::get('/payment-terms', [PaymentTermController::class, 'index']);
        Route::post('/payment-terms', [PaymentTermController::class, 'store']);
        Route::get('/payment-terms/{payment_term}', [PaymentTermController::class, 'show']);
        Route::put('/payment-terms/{payment_term}', [PaymentTermController::class, 'update']);
        Route::delete('/payment-terms/{payment_term}', [PaymentTermController::class, 'destroy']);
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);
        Route::put('/customers/{id}', [CustomerController::class, 'update']);
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    });
});