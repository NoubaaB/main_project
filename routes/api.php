<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierInvoiceController;

#region AI routes
Route::post('/ai-assistant', [App\Http\Controllers\AIAssistantController::class, 'ask']);
Route::post('/ask-db', [App\Http\Controllers\AIQueryController::class, 'ask']);
// Route::post('/ask-db', [App\Http\Controllers\DeepSeekController::class, 'ask']);
#endregion

#region non-authentication routes
Route::controller(AuthController::class)->group(function () {
    //register route
    Route::post("register", "register");
    //login route
    Route::post("login", "login");
});
#endregion 


#region authentication routes
Route::middleware(["auth:sanctum"])->group(
    function () {
        #region auth routes
        Route::get("me", [AuthController::class, "me"]); //get auth user with
        Route::post("truck", [TruckController::class, "store"]);
        Route::post("supplier", [SupplierController::class, "store"]);
        Route::post("logout", [AuthController::class, "logout"]);
        Route::post("update", [AuthController::class, "update"]);
        Route::post("charge", [ChargeController::class, "charge"]);
        Route::get("charge/{charge}", [ChargeController::class, "show"]);
        Route::delete("charge", [ChargeController::class, "destroy"]);
        Route::get("charges/get_charges", [ChargeController::class, "get_charges"]);
        Route::post("supplier_invoice", [SupplierInvoiceController::class, "store"]);
        Route::get("supplier_invoice", [SupplierInvoiceController::class, "index"]);
        Route::delete("supplier_invoice", [SupplierInvoiceController::class, "destroy"]);
        Route::get("supplier_invoice/supplier-invoices-by-ids", [SupplierInvoiceController::class, "supplierInvoicesByIds"]);
        Route::get("supplier_invoice/supplier-invoices-monthly-overview", [SupplierInvoiceController::class, "supplierInvoicesMonthlyOverview"]);
        Route::get("supplier_invoice/supplier-invoices-monthly-supplier", [SupplierInvoiceController::class, "supplierInvoicesMonthlyForSupplier"]);
        Route::get("supplier_invoice/supplier-invoices-monthly-cards", [SupplierInvoiceController::class, "supplierInvoicesMonthlyCards"]);
    #endregion
    });
#endregion