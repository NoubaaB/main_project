<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ReceiptController;

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
        Route::post("logout", [AuthController::class, "logout"]);
        Route::post("update", [AuthController::class, "update"]);
        Route::post("charge", [ChargeController::class, "charge"]);
        Route::get("get_charges", [ChargeController::class, "get_charges"]);
    #endregion
    });
#endregion