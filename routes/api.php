<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchesController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DeliverablesController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\PurchasesController;
use App\Http\Controllers\ReturnDeliverablesController;
use App\Http\Controllers\StocksController;
use App\Http\Controllers\StoresController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/token',[AuthController::class, 'token'])->name('token');
Route::post('/logout',[AuthController::class, 'logOut']);

Route::middleware(['auth:sanctum'])->group(function () {
    
    //Users
    Route::prefix('/users')->group(function () {
        Route::get('/',[UsersController::class, 'index']);
        Route::get('/create',[UsersController::class, 'create']);
        Route::post('/store',[UsersController::class, 'store']);
        Route::get('/edit/{id}',[UsersController::class, 'edit']);
        Route::put('/update/{id}',[UsersController::class, 'update']);
        Route::delete('delete/{id}',[UsersController::class, 'destroy']);
    });

    //Roles
    Route::prefix('/roles')->group(function () {
        Route::get('/',[RolesController::class, 'index']);
        Route::get('/create',[RolesController::class, 'create']);
        Route::post('/store',[RolesController::class, 'store']);
        Route::get('/edit/{id}',[RolesController::class, 'edit']);
        Route::put('/update/{id}',[RolesController::class, 'update']);
        Route::delete('/delete/{id}',[RolesController::class, 'destroy']);
    });

    //Stores
    Route::prefix('/stores')->group(function () {
        Route::get('/',[StoresController::class, 'index']);
        Route::get('/create',[StoresController::class, 'create']);
        Route::post('/save',[StoresController::class, 'save']);
        Route::get('/edit/{id}',[StoresController::class, 'edit']);
        Route::put('/update/{id}',[StoresController::class, 'update']);
    });

    //Branches
    Route::prefix('/branches')->group(function () {
        Route::get('/',[BranchesController::class, 'index']);
        Route::get('/create',[BranchesController::class, 'create']);
        Route::post('/save',[BranchesController::class, 'save']);
        Route::get('/edit/{id}',[BranchesController::class, 'edit']);
        Route::put('/update/{id}',[BranchesController::class, 'update']);
    });

    //Products
    Route::prefix('/products')->group(function () {
        Route::get('/',[ProductsController::class, 'index']);
        Route::get('/create',[ProductsController::class, 'create']);
        Route::post('/store',[ProductsController::class, 'store']);
        Route::get('/edit/{id}',[ProductsController::class, 'edit']);
        Route::post('/update/{id}',[ProductsController::class, 'update']);
        Route::put('/update/sale-price/{id}',[ProductsController::class, 'updateSalePrice']);
    });

    //Purchases
    Route::prefix('/purchases')->group(function () {
        Route::get('/',[PurchasesController::class, 'index']);
        Route::get('/create',[PurchasesController::class, 'create']);
        Route::post('/store',[PurchasesController::class, 'store']);
        Route::get('/edit/{id}',[PurchasesController::class, 'edit']);
        Route::put('/update/{id}',[PurchasesController::class, 'update']);
    });

    //Deliverables
    Route::prefix('/deliverables')->group(function () {
        Route::get('/',[DeliverablesController::class, 'index']);
        Route::get('/create',[DeliverablesController::class, 'create']);
        Route::get('/store-branches-products/{id}',[DeliverablesController::class, 'fetchStoreBranchesProducts']);
        Route::post('/store',[DeliverablesController::class, 'store']);
        Route::get('/edit/{id}',[DeliverablesController::class, 'edit']);
        Route::get('/fetch-deliverable-for-print/{id}',[DeliverablesController::class, 'fetchDeliverableById']);
        Route::put('/update/{id}',[DeliverablesController::class, 'update']);
    });
    //Return Deliverables
    Route::prefix('/return-deliverables')->group(function () {
        Route::get('/',[ReturnDeliverablesController::class, 'index']);
        Route::get('/create',[ReturnDeliverablesController::class, 'create']);
        Route::get('/store-branches/{id}',[ReturnDeliverablesController::class, 'getBranchesByStoreId']);
        Route::get('/branch-products/{id}',[ReturnDeliverablesController::class, 'getProductsByBranchId']);
        Route::post('/store',[ReturnDeliverablesController::class, 'store']);
        Route::get('/edit/{id}',[ReturnDeliverablesController::class, 'edit']);
        Route::get('/fetch-deliverable-for-print/{id}',[ReturnDeliverablesController::class, 'fetchDeliverableById']);
        Route::put('/update/{id}',[ReturnDeliverablesController::class, 'update']);
    });
    //Invoices
    Route::prefix('/invoices')->group(function () {
        Route::get('/',[InvoicesController::class, 'index']);
        Route::get('/create',[InvoicesController::class, 'create']);
        Route::get('/store-branches-products/{id}',[InvoicesController::class, 'fetchStoreBranchesProducts']);
        Route::post('/store',[InvoicesController::class, 'store']);
        // Route::get('/edit/{id}',[ReturnDeliverablesController::class, 'edit']);
        Route::get('/fetch-invoice-for-print/{id}',[InvoicesController::class, 'fetchInvoiceById']);
        // Route::put('/update/{id}',[ReturnDeliverablesController::class, 'update']);
    });
    //company
    Route::prefix('/company-detail')->group(function () {
        Route::get('/',[CompanyController::class, 'get']);
        Route::post('/update',[CompanyController::class, 'update']);
    });
    //stock
    Route::prefix('/stocks')->group(function () {
        Route::get('/',[StocksController::class, 'index']);
        Route::get('/fetch-branches/{id}',[StocksController::class, 'fetchBranchesByStoreId']);
        Route::get('/fetch-branch-stock/{id}',[StocksController::class, 'fetchStockByBranchId']);
    });


});

