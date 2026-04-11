<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierInvoiceController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\SellController;

#region AI routes
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
        Route::post('/ai-assistant', [App\Http\Controllers\AIAssistantController::class, 'ask']);
        Route::post('/ask-db', [App\Http\Controllers\AIQueryController::class, 'ask']);
        Route::post("charge", [ChargeController::class, "charge"]);
        Route::get("charge/{charge}", [ChargeController::class, "show"]);
        Route::delete("charge", [ChargeController::class, "destroy"]);
        Route::get("charges/get_charges", [ChargeController::class, "get_charges"]);
        Route::get("charges/get_production_charges", [ChargeController::class, "get_production_charges"]);
        Route::get("charge/{charge}/audit-logs", [ChargeController::class, "getAuditLogs"]);
        Route::post("supplier_invoice", [SupplierInvoiceController::class, "store"]);
        Route::get("supplier_invoice", [SupplierInvoiceController::class, "index"]);
        Route::delete("supplier_invoice", [SupplierInvoiceController::class, "destroy"]);
        Route::get("supplier_invoice/undo-invoice-item/{charge}", [SupplierInvoiceController::class, "undoSupplierInvoiceItem"]);
        Route::get("supplier_invoice/supplier-invoices-by-ids", [SupplierInvoiceController::class, "supplierInvoicesByIds"]);
        Route::get("supplier_invoice/supplier-invoices-monthly-overview", [SupplierInvoiceController::class, "supplierInvoicesMonthlyOverview"]);
        Route::get("supplier_invoice/supplier-invoices-monthly-supplier", [SupplierInvoiceController::class, "supplierInvoicesMonthlyForSupplier"]);
        Route::get("supplier_invoice/supplier-invoices-monthly-cards", [SupplierInvoiceController::class, "supplierInvoicesMonthlyCards"]);
        Route::get("chat-messages", [ChatMessageController::class, "index"]);
        Route::post("chat-messages", [ChatMessageController::class, "store"]);
        Route::delete("chat-messages", [ChatMessageController::class, "destroyAll"]);
        Route::delete("chat-messages/{id}", [ChatMessageController::class, "destroy"]);
        Route::post("chat-messages/{id}/react", [ChatMessageController::class, "react"]);

        // Sell routes
        Route::post("sell", [SellController::class, "sell"]);
        Route::get("sell/{sell}", [SellController::class, "show"]);
        Route::delete("sell", [SellController::class, "destroy"]);
        Route::get("sells/get_sells", [SellController::class, "get_sells"]);

        // Warehouse routes
        Route::get("warehouses/dashboard-stats", [WarehouseController::class, "dashboardStats"]);
        Route::get("warehouses/global-search", [WarehouseController::class, "globalSearch"]);
        Route::get("warehouses", [WarehouseController::class, "index"]);
        Route::get("warehouses/{warehouse}/stock-details", [WarehouseController::class, "stockDetails"]);
        Route::get("warehouses/{warehouse}/tracking-number/{tn}/transactions", [WarehouseController::class, "trackingNumberTransactions"]);
        Route::put("warehouses/tracking-number/{tn}/wap", [WarehouseController::class, "updateWap"]);
        Route::get("warehouses/{warehouse}/pie-chart", [WarehouseController::class, "pieChart"]);
        Route::get("warehouses/{warehouse}/stock-summary", [WarehouseController::class, "stockSummary"]);

        // Séjour routes (BEFORE generic warehouse CRUD to avoid route collision)
        Route::get("warehouses/{warehouse}/sejour", [WarehouseController::class, "calculateSejour"]);
        Route::get("warehouses/{warehouse}/sejour-rates", [WarehouseController::class, "sejourRates"]);
        Route::post("warehouses/{warehouse}/sejour-rates", [WarehouseController::class, "storeSejourRate"]);
        Route::put("warehouses/sejour-rates/{id}", [WarehouseController::class, "updateSejourRate"]);
        Route::delete("warehouses/sejour-rates/{id}", [WarehouseController::class, "deleteSejourRate"]);

        // Warehouse CRUD
        Route::post("warehouses", [WarehouseController::class, "store"]);
        Route::put("warehouses/{warehouse}", [WarehouseController::class, "update"]);
        Route::delete("warehouses/{warehouse}", [WarehouseController::class, "destroy"]);

        // Warehouse Category routes
        Route::get("warehouse-categories", [App\Http\Controllers\WarehouseCategoryController::class, "index"]);
        Route::post("warehouse-categories", [App\Http\Controllers\WarehouseCategoryController::class, "store"]);
        Route::put("warehouse-categories/{id}", [App\Http\Controllers\WarehouseCategoryController::class, "update"]);
        Route::delete("warehouse-categories/{id}", [App\Http\Controllers\WarehouseCategoryController::class, "destroy"]);

        // Inventory routes
        Route::get("inventory/{warehouse}/articles", [App\Http\Controllers\InventoryController::class, "articles"]);
        Route::post("inventory/adjust", [App\Http\Controllers\InventoryController::class, "adjust"]);
        Route::post("inventory/add-tracking", [App\Http\Controllers\InventoryController::class, "addTracking"]);
        Route::post("inventory/add-article", [App\Http\Controllers\InventoryController::class, "addArticle"]);
        Route::delete("inventory/tracking/{tn}", [App\Http\Controllers\InventoryController::class, "deleteTracking"]);
        Route::get("inventory/adjustments", [App\Http\Controllers\InventoryController::class, "adjustmentHistory"]);
        Route::get("inventory/adjustments/{id}", [App\Http\Controllers\InventoryController::class, "adjustmentDetail"]);
        Route::put("inventory/adjustments/{id}", [App\Http\Controllers\InventoryController::class, "updateAdjustment"]);
        Route::delete("inventory/adjustments/{id}", [App\Http\Controllers\InventoryController::class, "deleteAdjustment"]);

        // Fish Management routes
        Route::get("fish-families", [App\Http\Controllers\FishFamilyController::class, "index"]);
        Route::post("fish-families", [App\Http\Controllers\FishFamilyController::class, "store"]);
        Route::put("fish-families/{id}", [App\Http\Controllers\FishFamilyController::class, "update"]);
        Route::delete("fish-families/{id}", [App\Http\Controllers\FishFamilyController::class, "destroy"]);

        Route::get("fish-types", [App\Http\Controllers\FishTypeController::class, "index"]);
        Route::post("fish-types", [App\Http\Controllers\FishTypeController::class, "store"]);
        Route::put("fish-types/{id}", [App\Http\Controllers\FishTypeController::class, "update"]);
        Route::delete("fish-types/{id}", [App\Http\Controllers\FishTypeController::class, "destroy"]);

        Route::get("fish-process-types", [App\Http\Controllers\FishProcessTypeController::class, "index"]);
        Route::post("fish-process-types", [App\Http\Controllers\FishProcessTypeController::class, "store"]);
        Route::put("fish-process-types/{id}", [App\Http\Controllers\FishProcessTypeController::class, "update"]);
        Route::delete("fish-process-types/{id}", [App\Http\Controllers\FishProcessTypeController::class, "destroy"]);

        // Production Yield
        Route::get("production-yield", [App\Http\Controllers\ProductionYieldController::class, "getYield"]);
    #endregion
    });
#endregion