<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssetPrintController;

Route::get('/asset/print-qr/{id}', [AssetPrintController::class, 'print'])->name('asset.print-qr');

Route::get('/', function () {
    return view('welcome');
});
