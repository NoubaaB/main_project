<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierInvoiceController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\SejourRateController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\InventoryController;

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
        // Broadcasting auth
        Broadcast::routes();
        #region auth routes
        Route::get("me", [AuthController::class, "me"]); //get auth user with
        Route::post("truck", [TruckController::class, "store"]);
        Route::post("supplier", [SupplierController::class, "store"]);
        Route::post("ship", [App\Http\Controllers\ShipController::class, "store"]);
        Route::post("logout", [AuthController::class, "logout"]);
        Route::post("update", [AuthController::class, "update"]);
        Route::post('/ai-assistant', [App\Http\Controllers\AIAssistantController::class, 'ask']);
        Route::post('/ai-assistant/execute', [App\Http\Controllers\AIAssistantController::class, 'executeAction']);
        Route::post('/ai-assistant/excel', [App\Http\Controllers\AIAssistantController::class, 'askWithExcel']);
        Route::post('/ask-db', [App\Http\Controllers\AIQueryController::class, 'ask']);

        // ══════════════════════════════════════════════
        //  USER & ROLE MANAGEMENT (super_admin + users.manage)
        // ══════════════════════════════════════════════
        Route::middleware('super_admin')->group(function () {
            Route::get('admin/users', [App\Http\Controllers\UserManagementController::class, 'index']);
            Route::post('admin/users', [App\Http\Controllers\UserManagementController::class, 'store']);
            Route::put('admin/users/{user}', [App\Http\Controllers\UserManagementController::class, 'update']);
            Route::patch('admin/users/{user}/toggle-active', [App\Http\Controllers\UserManagementController::class, 'toggleActive']);
            Route::get('admin/roles', [App\Http\Controllers\RoleController::class, 'index']);
            Route::post('admin/roles', [App\Http\Controllers\RoleController::class, 'store']);
            Route::put('admin/roles/{role}', [App\Http\Controllers\RoleController::class, 'update']);
            Route::delete('admin/roles/{role}', [App\Http\Controllers\RoleController::class, 'destroy']);
        });

        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        //  CHARGE ROUTES
        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        Route::get("charges/get_charges", [ChargeController::class, "get_charges"])->middleware('check.permission:charges.view');
        Route::get("charge/{charge}", [ChargeController::class, "show"])->middleware('check.permission:charges.view');
        Route::post("charge", [ChargeController::class, "store"])->middleware('check.permission:charges.create');
        Route::put("charge/{charge}", [ChargeController::class, "update"])->middleware('check.permission:charges.edit');
        Route::delete("charge", [ChargeController::class, "destroy"])->middleware('check.permission:charges.delete');
        Route::post("charge/check-tracking-number", [ChargeController::class, "checkTrackingNumberUniqueness"]);
        Route::post("charge/check-onp-tracking-number", [ChargeController::class, "checkOnpTrackingNumberUniqueness"]);
        Route::get("charges/production-charges", [ChargeController::class, "get_production_charges"]);

        // Quality Control
        Route::get("quality-control/{production}", [App\Http\Controllers\QualityControlController::class, "show"]);
        Route::post("quality-control", [App\Http\Controllers\QualityControlController::class, "store"]);
        Route::put("quality-control/{qualityControl}", [App\Http\Controllers\QualityControlController::class, "update"]);
        Route::delete("quality-control/{qualityControl}", [App\Http\Controllers\QualityControlController::class, "destroy"]);
        Route::post("quality-control/{qualityControl}/approve", [App\Http\Controllers\QualityControlController::class, "approve"]);

        // Charge Categories
        Route::get("charge-categories", [App\Http\Controllers\ChargeCategoryController::class, "index"]);
        Route::post("charge-categories", [App\Http\Controllers\ChargeCategoryController::class, "store"]);
        Route::put("charge-categories/{chargeCategory}", [App\Http\Controllers\ChargeCategoryController::class, "update"]);
        Route::delete("charge-categories/{chargeCategory}", [App\Http\Controllers\ChargeCategoryController::class, "destroy"]);

        // Detail Charge Categories
        Route::get("detail-charge-categories", [App\Http\Controllers\DetailChargeCategoryController::class, "index"]);
        Route::post("detail-charge-categories", [App\Http\Controllers\DetailChargeCategoryController::class, "store"]);
        Route::put("detail-charge-categories/{id}", [App\Http\Controllers\DetailChargeCategoryController::class, "update"]);
        Route::delete("detail-charge-categories/{id}", [App\Http\Controllers\DetailChargeCategoryController::class, "destroy"]);

        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        //  SHIPPING ROUTES
        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        Route::get("shippings/get_shippings", [ShippingController::class, "get_shippings"])->middleware('check.permission:shippings.view');
        Route::get("shipping/{shipping}", [ShippingController::class, "show"])->middleware('check.permission:shippings.view');
        Route::post("shipping", [ShippingController::class, "store"])->middleware('check.permission:shippings.create');
        Route::put("shipping/{shipping}", [ShippingController::class, "update"])->middleware('check.permission:shippings.edit');
        Route::delete("shipping", [ShippingController::class, "destroy"])->middleware('check.permission:shippings.delete');
        Route::post("shipping/check-tracking-number", [ShippingController::class, "checkShippingTrackingNumberUniqueness"]);

        // Shipping Categories
        Route::get("shipping-categories", [App\Http\Controllers\ShippingCategoryController::class, "index"]);
        Route::post("shipping-categories", [App\Http\Controllers\ShippingCategoryController::class, "store"]);
        Route::put("shipping-categories/{shippingCategory}", [App\Http\Controllers\ShippingCategoryController::class, "update"]);
        Route::delete("shipping-categories/{shippingCategory}", [App\Http\Controllers\ShippingCategoryController::class, "destroy"]);

        // Detail Shipping Categories
        Route::get("detail-shipping-categories", [App\Http\Controllers\DetailShippingCategoryController::class, "index"]);
        Route::post("detail-shipping-categories", [App\Http\Controllers\DetailShippingCategoryController::class, "store"]);
        Route::put("detail-shipping-categories/{id}", [App\Http\Controllers\DetailShippingCategoryController::class, "update"]);
        Route::delete("detail-shipping-categories/{id}", [App\Http\Controllers\DetailShippingCategoryController::class, "destroy"]);

        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        //  PRODUCTION ROUTES
        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        Route::get("productions/analytics", [App\Http\Controllers\ProductionController::class, "analytics"])->middleware('check.permission:production.view');
        Route::get("productions", [App\Http\Controllers\ProductionController::class, "index"])->middleware('check.permission:production.view');
        Route::get("productions/{production}", [App\Http\Controllers\ProductionController::class, "show"])->middleware('check.permission:production.view');
        Route::post("productions", [App\Http\Controllers\ProductionController::class, "store"])->middleware('check.permission:production.create');
        Route::put("productions/{production}", [App\Http\Controllers\ProductionController::class, "update"])->middleware('check.permission:production.edit');
        Route::delete("productions", [App\Http\Controllers\ProductionController::class, "destroy"])->middleware('check.permission:production.delete');
        Route::post("productions/check-production-tracking-number", [App\Http\Controllers\ProductionController::class, "checkProductionTrackingNumberUniqueness"]);
        Route::post("productions/check-reception-tracking-number", [App\Http\Controllers\ProductionController::class, "checkReceptionTrackingNumberUniqueness"]);
        Route::post("productions/check-onp-tracking-number", [App\Http\Controllers\ProductionController::class, "checkOnpTrackingNumberUniqueness"]);

        // Production Categories
        Route::get("production-categories", [App\Http\Controllers\ProductionCategoryController::class, "index"]);
        Route::post("production-categories", [App\Http\Controllers\ProductionCategoryController::class, "store"]);
        Route::put("production-categories/{productionCategory}", [App\Http\Controllers\ProductionCategoryController::class, "update"]);
        Route::delete("production-categories/{productionCategory}", [App\Http\Controllers\ProductionCategoryController::class, "destroy"]);

        // Detail Production Categories
        Route::get("detail-production-categories", [App\Http\Controllers\DetailProductionCategoryController::class, "index"]);
        Route::post("detail-production-categories", [App\Http\Controllers\DetailProductionCategoryController::class, "store"]);
        Route::put("detail-production-categories/{id}", [App\Http\Controllers\DetailProductionCategoryController::class, "update"]);
        Route::delete("detail-production-categories/{id}", [App\Http\Controllers\DetailProductionCategoryController::class, "destroy"]);

        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        //  WAREHOUSE ROUTES
        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        Route::get("warehouses/dashboard-data", [WarehouseController::class, "dashboardData"])->middleware('check.permission:warehouses.view');
        Route::get("warehouses/available-balances", [WarehouseController::class, "availableBalances"])->middleware('check.permission:warehouses.view');
        Route::get("warehouses/with-stock", [WarehouseController::class, "warehousesWithStock"])->middleware('check.permission:warehouses.view');
        Route::get("warehouses/articles", [WarehouseController::class, "warehouseArticles"])->middleware('check.permission:warehouses.view');
        Route::get("warehouses/balances-for-transfer", [WarehouseController::class, "warehouseBalancesForTransfer"])->middleware('check.permission:warehouses.view');
        Route::get("warehouses", [WarehouseController::class, "index"])->middleware('check.permission:warehouses.view');
        Route::post("warehouses", [WarehouseController::class, "store"])->middleware('check.permission:warehouses.create');
        Route::put("warehouses/{warehouse}", [WarehouseController::class, "update"])->middleware('check.permission:warehouses.edit');
        Route::delete("warehouses/{warehouse}", [WarehouseController::class, "destroy"])->middleware('check.permission:warehouses.delete');

        // Warehouse Categories
        Route::get("warehouse-categories", [App\Http\Controllers\WarehouseCategoryController::class, "index"]);
        Route::post("warehouse-categories", [App\Http\Controllers\WarehouseCategoryController::class, "store"]);
        Route::put("warehouse-categories/{id}", [App\Http\Controllers\WarehouseCategoryController::class, "update"]);
        Route::delete("warehouse-categories/{id}", [App\Http\Controllers\WarehouseCategoryController::class, "destroy"]);

        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        //  INVENTORY ROUTES
        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        Route::get("inventories/get_inventories", [InventoryController::class, "get_inventories"])->middleware('check.permission:inventory.view');
        Route::get("inventory/{inventory}", [InventoryController::class, "show"])->middleware('check.permission:inventory.view');
        Route::post("inventory", [InventoryController::class, "store"])->middleware('check.permission:inventory.create');
        Route::post("inventory/check-tracking-number", [InventoryController::class, "checkTrackingNumberUniqueness"])->middleware('check.permission:inventory.create');
        Route::put("inventory/{inventory}", [InventoryController::class, "update"])->middleware('check.permission:inventory.edit');
        Route::delete("inventory", [InventoryController::class, "destroy"])->middleware('check.permission:inventory.delete');

        // Detail Inventory Categories
        Route::get("detail-inventory-categories", [App\Http\Controllers\DetailInventoryCategoryController::class, "index"]);
        Route::post("detail-inventory-categories", [App\Http\Controllers\DetailInventoryCategoryController::class, "store"]);
        Route::put("detail-inventory-categories/{id}", [App\Http\Controllers\DetailInventoryCategoryController::class, "update"]);
        Route::delete("detail-inventory-categories/{id}", [App\Http\Controllers\DetailInventoryCategoryController::class, "destroy"]);

        // Sejour Rates
        Route::get("warehouses/{warehouse}/sejour-rates", [SejourRateController::class, "index"]);
        Route::post("warehouses/{warehouse}/sejour-rates", [SejourRateController::class, "store"]);
        Route::put("warehouses/sejour-rates/{id}", [SejourRateController::class, "update"]);
        Route::delete("warehouses/sejour-rates/{id}", [SejourRateController::class, "destroy"]);

        // Storage Invoices (Facture de Stockage)
        Route::get("storage-invoices", [App\Http\Controllers\StorageInvoiceController::class, "index"]);
        Route::get("storage-invoices/preview", [App\Http\Controllers\StorageInvoiceController::class, "preview"]);
        Route::post("storage-invoices/generate", [App\Http\Controllers\StorageInvoiceController::class, "generate"]);
        Route::get("storage-invoices/{id}", [App\Http\Controllers\StorageInvoiceController::class, "show"]);
        Route::delete("storage-invoices/{id}", [App\Http\Controllers\StorageInvoiceController::class, "destroy"]);
        Route::post("storage-invoices/{id}/increment-print-counter", [App\Http\Controllers\StorageInvoiceController::class, "incrementPrintCounter"]);

        // â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• 
        //  SUPPLIER INVOICE ROUTES
        // â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• 
        Route::get("supplier_invoice", [SupplierInvoiceController::class, "index"])->middleware('check.permission:supplier_invoices.view');
        Route::get("supplier_invoice/supplier-invoices-by-ids", [SupplierInvoiceController::class, "supplierInvoicesByIds"])->middleware('check.permission:supplier_invoices.view');
        Route::get("supplier_invoice/check-tracking-number", [SupplierInvoiceController::class, "checkTrackingNumber"])->middleware('check.permission:supplier_invoices.view');
        Route::post("supplier_invoice", [SupplierInvoiceController::class, "store"])->middleware('check.permission:supplier_invoices.create');
        Route::post("supplier_invoice/increment-print-counter", [SupplierInvoiceController::class, "incrementPrintCounter"])->middleware('check.permission:supplier_invoices.view');
        Route::delete("supplier_invoice", [SupplierInvoiceController::class, "destroy"])->middleware('check.permission:supplier_invoices.delete');
        Route::get("supplier_invoice/next-tracking-number", [SupplierInvoiceController::class, "nextTrackingNumber"]);

        // Supplier Payments
        Route::get("supplier_payments/balance", [\App\Http\Controllers\SupplierPaymentController::class, "supplierBalance"]);
        Route::get("supplier_payments/year-summary", [\App\Http\Controllers\SupplierPaymentController::class, "yearSummary"]);
        Route::get("supplier_payments", [\App\Http\Controllers\SupplierPaymentController::class, "index"]);
        Route::post("supplier_payments", [\App\Http\Controllers\SupplierPaymentController::class, "store"]);
        Route::put("supplier_payments/{supplierPayment}", [\App\Http\Controllers\SupplierPaymentController::class, "update"]);
        Route::delete("supplier_payments", [\App\Http\Controllers\SupplierPaymentController::class, "destroy"]);

        // Fiscal Year Closings
        Route::get("fiscal_year_closings", [\App\Http\Controllers\FiscalYearClosingController::class, "index"]);
        Route::post("fiscal_year_closings", [\App\Http\Controllers\FiscalYearClosingController::class, "close"]);
        Route::put("fiscal_year_closings/{fiscalYearClosing}/reopen", [\App\Http\Controllers\FiscalYearClosingController::class, "reopen"]);
        Route::get("fiscal_year_closings/{fiscalYearClosing}", [\App\Http\Controllers\FiscalYearClosingController::class, "show"]);

        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        //  CLIENT INVOICE ROUTES
        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        Route::get("client_invoice", [\App\Http\Controllers\ClientInvoiceController::class, "index"])->middleware('check.permission:client_invoices.view');
        Route::get("client_invoice/check-tracking-number", [\App\Http\Controllers\ClientInvoiceController::class, "checkTrackingNumber"])->middleware('check.permission:client_invoices.view');
        Route::get("client_invoice/next-tracking-number", [\App\Http\Controllers\ClientInvoiceController::class, "nextTrackingNumber"])->middleware('check.permission:client_invoices.view');
        Route::get("client_invoice/client-invoices-by-ids", [\App\Http\Controllers\ClientInvoiceController::class, "clientInvoicesByIds"])->middleware('check.permission:client_invoices.view');
        Route::post("client_invoice", [\App\Http\Controllers\ClientInvoiceController::class, "store"])->middleware('check.permission:client_invoices.create');
        Route::post("client_invoice/increment-print-counter", [\App\Http\Controllers\ClientInvoiceController::class, "incrementPrintCounter"])->middleware('check.permission:client_invoices.view');
        Route::delete("client_invoice", [\App\Http\Controllers\ClientInvoiceController::class, "destroy"])->middleware('check.permission:client_invoices.delete');

        // Client Payments
        Route::get("client_payments/balance", [\App\Http\Controllers\ClientPaymentController::class, "clientBalance"]);
        Route::get("client_payments/year-summary", [\App\Http\Controllers\ClientPaymentController::class, "yearSummary"]);
        Route::get("client_payments", [\App\Http\Controllers\ClientPaymentController::class, "index"]);
        Route::post("client_payments", [\App\Http\Controllers\ClientPaymentController::class, "store"]);
        Route::put("client_payments/{clientPayment}", [\App\Http\Controllers\ClientPaymentController::class, "update"]);
        Route::delete("client_payments", [\App\Http\Controllers\ClientPaymentController::class, "destroy"]);

        // Client Fiscal Year Closings
        Route::get("client_fiscal_year_closings", [\App\Http\Controllers\ClientFiscalYearClosingController::class, "index"]);
        Route::post("client_fiscal_year_closings", [\App\Http\Controllers\ClientFiscalYearClosingController::class, "store"]);
        Route::put("client_fiscal_year_closings/{clientFiscalYearClosing}/reopen", [\App\Http\Controllers\ClientFiscalYearClosingController::class, "reopen"]);

        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        //  CHAT ROUTES
        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        Route::get("chat-messages", [ChatMessageController::class, "index"]);
        Route::post("chat-messages", [ChatMessageController::class, "store"]);
        Route::delete("chat-messages", [ChatMessageController::class, "destroyAll"]);
        Route::delete("chat-messages/{id}", [ChatMessageController::class, "destroy"]);
        Route::post("chat-messages/{id}/react", [ChatMessageController::class, "react"]);

        // AI Query messages
        Route::get("ai-query-messages", [App\Http\Controllers\AiQueryMessageController::class, "index"]);
        Route::post("ai-query-messages", [App\Http\Controllers\AiQueryMessageController::class, "store"]);
        Route::delete("ai-query-messages", [App\Http\Controllers\AiQueryMessageController::class, "destroyAll"]);
        Route::delete("ai-query-messages/{id}", [App\Http\Controllers\AiQueryMessageController::class, "destroy"]);
        Route::post("ai-query-messages/{id}/react", [App\Http\Controllers\AiQueryMessageController::class, "react"]);

        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        //  FISH MANAGEMENT ROUTES
        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        Route::get("fish-families", [App\Http\Controllers\FishFamilyController::class, "index"]);
        Route::post("fish-families", [App\Http\Controllers\FishFamilyController::class, "store"]);
        Route::put("fish-families/{id}", [App\Http\Controllers\FishFamilyController::class, "update"]);
        Route::delete("fish-families/{id}", [App\Http\Controllers\FishFamilyController::class, "destroy"]);

        Route::get("fish-types", [App\Http\Controllers\FishTypeController::class, "index"]);
        Route::post("fish-types", [App\Http\Controllers\FishTypeController::class, "store"]);
        Route::put("fish-types/{id}", [App\Http\Controllers\FishTypeController::class, "update"]);
        Route::delete("fish-types/{id}", [App\Http\Controllers\FishTypeController::class, "destroy"]);

        Route::get("fish-types/{fishType}/waps", [App\Http\Controllers\FishTypeWapController::class, "index"]);
        Route::post("fish-types/{fishType}/waps", [App\Http\Controllers\FishTypeWapController::class, "store"]);
        Route::put("fish-type-waps/{id}", [App\Http\Controllers\FishTypeWapController::class, "update"]);
        Route::delete("fish-type-waps/{id}", [App\Http\Controllers\FishTypeWapController::class, "destroy"]);

        Route::get("fish-process-types", [App\Http\Controllers\FishProcessTypeController::class, "index"]);
        Route::post("fish-process-types", [App\Http\Controllers\FishProcessTypeController::class, "store"]);
        Route::put("fish-process-types/{id}", [App\Http\Controllers\FishProcessTypeController::class, "update"]);
        Route::delete("fish-process-types/{id}", [App\Http\Controllers\FishProcessTypeController::class, "destroy"]);

        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        //  WORKFORCE ROUTES
        // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
        Route::apiResource("corporals", App\Http\Controllers\CorporalController::class);
        Route::apiResource("sections", App\Http\Controllers\SectionController::class);
        Route::post("sections/{section}/corporals", [App\Http\Controllers\SectionController::class, "attachCorporals"]);
        Route::delete("sections/{section}/corporals/{corporal}", [App\Http\Controllers\SectionController::class, "detachCorporal"]);
        Route::apiResource("workforces", App\Http\Controllers\WorkforceController::class);
        Route::get("fortnight-activities/activities-by-date", [App\Http\Controllers\FortnightActivityController::class, "activitiesByDate"]);
        Route::get("fortnight-activities/daily-totals", [App\Http\Controllers\FortnightActivityController::class, "dailyTotals"]);
        Route::get("fortnight-activities/summary", [App\Http\Controllers\FortnightActivityController::class, "summary"]);
        Route::get("fortnight-activities/breakdown", [App\Http\Controllers\FortnightActivityController::class, "breakdown"]);
        Route::get("fortnight-activities/export", [App\Http\Controllers\FortnightActivityController::class, "export"]);
        Route::get("fortnight-activities/statistics", [App\Http\Controllers\FortnightActivityController::class, "statistics"]);
        Route::post("fortnight-activities/bulk-delete", [App\Http\Controllers\FortnightActivityController::class, "bulkDeleteByDate"]);
        Route::get("fortnight-activities", [App\Http\Controllers\FortnightActivityController::class, "index"]);
        Route::post("fortnight-activities", [App\Http\Controllers\FortnightActivityController::class, "store"]);
        Route::put("fortnight-activities/{fortnightActivity}", [App\Http\Controllers\FortnightActivityController::class, "update"]);
        Route::delete("fortnight-activities/{fortnightActivity}", [App\Http\Controllers\FortnightActivityController::class, "destroy"]);

        // Fortnight Settings
        Route::get("fortnight-settings", [App\Http\Controllers\FortnightSettingController::class, "index"]);
        Route::post("fortnight-settings", [App\Http\Controllers\FortnightSettingController::class, "store"]);
        Route::put("fortnight-settings/{id}", [App\Http\Controllers\FortnightSettingController::class, "update"]);
        Route::delete("fortnight-settings/{id}", [App\Http\Controllers\FortnightSettingController::class, "destroy"]);

        // Static Charges
        Route::get("static-charges", [App\Http\Controllers\StaticChargeController::class, "index"]);
        Route::post("static-charges", [App\Http\Controllers\StaticChargeController::class, "store"]);
        Route::put("static-charges/{id}", [App\Http\Controllers\StaticChargeController::class, "update"]);
        Route::delete("static-charges/{id}", [App\Http\Controllers\StaticChargeController::class, "destroy"]);

        // Ships
        Route::post("ships", [App\Http\Controllers\ShipController::class, "store"]);

        // Clients
        Route::post("clients", [App\Http\Controllers\ClientController::class, "store"]);

        // ═══════════════════════════════════════════
        //  WAREHOUSE TRANSFER ROUTES
        // ═══════════════════════════════════════════
        Route::get("warehouse-transfer-categories", [App\Http\Controllers\WarehouseTransferCategoryController::class, "index"]);
        Route::post("warehouse-transfer-categories", [App\Http\Controllers\WarehouseTransferCategoryController::class, "store"]);
        Route::put("warehouse-transfer-categories/{id}", [App\Http\Controllers\WarehouseTransferCategoryController::class, "update"]);
        Route::delete("warehouse-transfer-categories/{id}", [App\Http\Controllers\WarehouseTransferCategoryController::class, "destroy"]);

        Route::get("warehouse-transfers/get_transfers", [App\Http\Controllers\WarehouseTransferController::class, "get_transfers"])->middleware('check.permission:warehouse_transfers.view');
        Route::get("warehouse-transfer/{warehouseTransfer}", [App\Http\Controllers\WarehouseTransferController::class, "show"])->middleware('check.permission:warehouse_transfers.view');
        Route::post("warehouse-transfer", [App\Http\Controllers\WarehouseTransferController::class, "store"])->middleware('check.permission:warehouse_transfers.create');
        Route::post("warehouse-transfer/check-tracking-number", [App\Http\Controllers\WarehouseTransferController::class, "checkTrackingNumberUniqueness"])->middleware('check.permission:warehouse_transfers.create');
        Route::put("warehouse-transfer/{warehouseTransfer}", [App\Http\Controllers\WarehouseTransferController::class, "update"])->middleware('check.permission:warehouse_transfers.edit');
        Route::delete("warehouse-transfer", [App\Http\Controllers\WarehouseTransferController::class, "destroy"])->middleware('check.permission:warehouse_transfers.delete');
        #endregion
    
        #region Guano Types
        Route::get("guano-types", [App\Http\Controllers\GuanoTypeController::class, "index"]);
        Route::post("guano-types", [App\Http\Controllers\GuanoTypeController::class, "store"]);
        Route::put("guano-types/{guanoType}", [App\Http\Controllers\GuanoTypeController::class, "update"]);
        Route::delete("guano-types/{guanoType}", [App\Http\Controllers\GuanoTypeController::class, "destroy"]);
        #endregion
    
        #region Package Types
        Route::get("package-types", [App\Http\Controllers\PackageTypeController::class, "index"]);
        Route::post("package-types", [App\Http\Controllers\PackageTypeController::class, "store"]);
        Route::put("package-types/{packageType}", [App\Http\Controllers\PackageTypeController::class, "update"]);
        Route::delete("package-types/{packageType}", [App\Http\Controllers\PackageTypeController::class, "destroy"]);

        Route::get("package-secondary-configs", [App\Http\Controllers\PackageSecondaryConfigController::class, "index"]);
        Route::post("package-secondary-configs", [App\Http\Controllers\PackageSecondaryConfigController::class, "store"]);
        Route::put("package-secondary-configs/{id}", [App\Http\Controllers\PackageSecondaryConfigController::class, "update"]);
        Route::delete("package-secondary-configs/{id}", [App\Http\Controllers\PackageSecondaryConfigController::class, "destroy"]);
        Route::get("package-secondary-configs/active", [App\Http\Controllers\PackageSecondaryConfigController::class, "getActiveConfig"]);
        #endregion
    
        #region Bank Names
        Route::get("bank-names", [App\Http\Controllers\BankNameController::class, "index"]);
        Route::post("bank-names", [App\Http\Controllers\BankNameController::class, "store"]);
        Route::put("bank-names/{bankName}", [App\Http\Controllers\BankNameController::class, "update"]);
        Route::delete("bank-names/{bankName}", [App\Http\Controllers\BankNameController::class, "destroy"]);
        #endregion
    
        // ═══════════════════════════════════════════
        //  ORDER TRANSFER ROUTES
        // ═══════════════════════════════════════════
        Route::get("order-transfers/analytics", [App\Http\Controllers\OrderTransferController::class, "analytics"])->middleware('check.permission:order_transfers.view');
        Route::get("order-transfers", [App\Http\Controllers\OrderTransferController::class, "index"])->middleware('check.permission:order_transfers.view');
        Route::get("order-transfers/{orderTransfer}", [App\Http\Controllers\OrderTransferController::class, "show"])->middleware('check.permission:order_transfers.view');
        Route::post("order-transfers", [App\Http\Controllers\OrderTransferController::class, "store"])->middleware('check.permission:order_transfers.create');
        Route::put("order-transfers/{orderTransfer}", [App\Http\Controllers\OrderTransferController::class, "update"])->middleware('check.permission:order_transfers.edit');
        Route::delete("order-transfers", [App\Http\Controllers\OrderTransferController::class, "destroy"])->middleware('check.permission:order_transfers.delete');

        // ═══════════════════════════════════════════
        //  COMPANY SETTINGS ROUTES
        // ═══════════════════════════════════════════
        Route::get("company-settings", [App\Http\Controllers\CompanySettingController::class, "index"]);
        Route::put("company-settings", [App\Http\Controllers\CompanySettingController::class, "update"]);
        Route::post("company-settings/upload-logo", [App\Http\Controllers\CompanySettingController::class, "uploadLogo"]);

        // ═══════════════════════════════════════════
        //  CUSTOM CHART ROUTES
        // ═══════════════════════════════════════════
        Route::get("custom-charts", [App\Http\Controllers\CustomChartController::class, "index"]);
        Route::post("custom-charts", [App\Http\Controllers\CustomChartController::class, "store"]);
        Route::get("custom-charts/{customChart}", [App\Http\Controllers\CustomChartController::class, "show"]);
        Route::put("custom-charts/{customChart}", [App\Http\Controllers\CustomChartController::class, "update"]);
        Route::delete("custom-charts/{customChart}", [App\Http\Controllers\CustomChartController::class, "destroy"]);
        Route::get("custom-charts/{customChart}/data", [App\Http\Controllers\CustomChartController::class, "chartData"]);
        Route::post("custom-charts/reorder", [App\Http\Controllers\CustomChartController::class, "reorder"]);

        // ═══════════════════════════════════════════
        //  NOTIFICATION ROUTES
        // ═══════════════════════════════════════════
        Route::get("notifications", [App\Http\Controllers\NotificationController::class, "index"]);
        Route::get("notifications/unread-count", [App\Http\Controllers\NotificationController::class, "unreadCount"]);
        Route::put("notifications/read-all", [App\Http\Controllers\NotificationController::class, "markAllAsRead"]);
        Route::put("notifications/{id}/read", [App\Http\Controllers\NotificationController::class, "markAsRead"]);

        // ═══════════════════════════════════════════
        //  AUDIT LOG ROUTES
        // ═══════════════════════════════════════════
        Route::get("audit-logs", [App\Http\Controllers\AuditLogController::class, "index"]);

        // ═══════════════════════════════════════════
        //  USER STATISTICS ROUTES
        // ═══════════════════════════════════════════
        Route::get("user-statistics", [App\Http\Controllers\UserStatisticsController::class, "index"]);
        // ═══════════════════════════════════════════
        //  COST CONFIGURATION ROUTES
        // ═══════════════════════════════════════════
        Route::get("cost-configurations/categories-and-sources", [App\Http\Controllers\CostConfigurationController::class, "categoriesAndSources"]);
        Route::post("cost-configurations/evaluate", [App\Http\Controllers\CostConfigurationController::class, "evaluate"]);
        Route::apiResource("cost-configurations", App\Http\Controllers\CostConfigurationController::class);
    }
);
#endregion
