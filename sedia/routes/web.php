<?php

use App\Support\OutletContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('dashboard')
    ->middleware('auth')
    ->group(function () {
        Route::post('/outlet-context', function (Request $request) {
            $data = $request->validate([
                'selected_outlet_id' => ['nullable', 'integer'],
            ]);

            OutletContext::setCurrentOutletId($data['selected_outlet_id'] ?? null);

            return back();
        })->name('dashboard.outlet-context.update');
    });
