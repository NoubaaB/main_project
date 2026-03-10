<?php

use Illuminate\Support\Facades\Route;

// Route::get('/ask-db', [App\Http\Controllers\AIQueryController::class, 'ask']);
Route::get('/sad_spa', fn() => view("system_architecture"));

Route::get('/{vue_capture?}', fn() => view('welcome'))->where('vue_capture', '[\/\w\.-]*');
