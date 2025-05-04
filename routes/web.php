<?php

use Illuminate\Support\Facades\Route;

// Route::get('/ask-db', [App\Http\Controllers\AIQueryController::class, 'ask']);

Route::get('/{vue_capture?}', function () {
    return view('welcome');
})->where('vue_capture', '[\/\w\.-]*');
