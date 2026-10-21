<?php

use Illuminate\Support\Facades\Route;

// Route::get('/ask-db', [App\Http\Controllers\AIQueryController::class, 'ask']);
Route::get('/sad_spa', fn() => view("system_architecture"));

// Dynamic PWA Manifest syncing theme_color and background_color
Route::get('/manifest.json', function (\Illuminate\Http\Request $request) {
    $theme = $request->query('theme', 'dark');
    $isDark = $theme === 'dark';
    $themeColor = $isDark ? '#121212' : '#FFFFFF';
    $bgColor = $isDark ? '#121212' : '#FFFFFF';
    
    $manifestPath = public_path('js/manifest.json');
    $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
    $manifest['theme_color'] = $themeColor;
    $manifest['background_color'] = $bgColor;
    
    return response()->json($manifest)
        ->header('Content-Type', 'application/manifest+json; charset=utf-8');
});

Route::get('/{vue_capture?}', fn() => view('welcome'))
    ->where('vue_capture', '[\/\w\.-]*');

