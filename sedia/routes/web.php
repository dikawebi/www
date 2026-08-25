<?php

use App\Models\SalesTransaction;
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

Route::middleware('auth')->get('/receipt/{record}', function (SalesTransaction $record) {
    $user = OutletContext::user();
    if ($user && ! $user->isAdmin() && $record->outlet_id !== $user->outlet_id) {
        abort(403);
    }
    $record->load(['outlet', 'cashier', 'items.menuItem']);

    return view('receipt', ['transaction' => $record]);
})->name('receipt.show');
