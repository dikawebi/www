<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssetPrintController;

// 🚀 TEMPORARY DEPLOYMENT TRIGGER ROUTE:
Route::get('/run-migration-safely', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate --force');
        \Illuminate\Support\Facades\Artisan::call('db:seed'); // Optional if you have seeders
        return "Database tables successfully initialized!";
    } catch (\Exception $e) {
        return "Migration failed: " . $e->getMessage();
    }
});

Route::get('/asset/print-qr/{id}', [AssetPrintController::class, 'print'])->name('asset.print-qr');

Route::get('/', function () {
    return view('welcome');
});
