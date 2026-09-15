<?php

use App\Models\SalesTransaction;
use App\Support\OutletContext;
use App\Http\Controllers\InertiaAuthController;
use App\Http\Controllers\InertiaDashboardController;
use App\Http\Controllers\InertiaReportController;
use App\Http\Controllers\InertiaMasterController;
use App\Http\Controllers\InertiaPosController;
use App\Http\Controllers\InertiaStockController;
use App\Http\Controllers\InertiaTransactionController;
use App\Http\Controllers\InertiaSettingsController;
use App\Http\Controllers\InertiaOperationsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('app')->group(function () {
    Route::get('/login', [InertiaAuthController::class, 'create'])->middleware('guest')->name('login');
    Route::post('/login', [InertiaAuthController::class, 'store'])->middleware('guest');
    Route::post('/logout', [InertiaAuthController::class, 'destroy'])->middleware('auth')->name('app.logout');

    Route::get('/', InertiaDashboardController::class)->middleware('auth')->name('app.dashboard');
    Route::get('/reports/{report}', InertiaReportController::class)->middleware('auth')->name('app.reports.show');
    Route::get('/master/{resource}', [InertiaMasterController::class, 'index'])->middleware('auth')->name('app.master.index');
    Route::post('/master/{resource}', [InertiaMasterController::class, 'store'])->middleware('auth')->name('app.master.store');
    Route::put('/master/{resource}/{id}', [InertiaMasterController::class, 'update'])->middleware('auth')->name('app.master.update');
    Route::delete('/master/{resource}/{id}', [InertiaMasterController::class, 'destroy'])->middleware('auth')->name('app.master.destroy');
    Route::get('/master/menus/{menu}/recipes', [InertiaMasterController::class, 'recipes'])->middleware('auth')->name('app.master.recipes');
    Route::post('/master/menus/{menu}/recipes', [InertiaMasterController::class, 'storeRecipe'])->middleware('auth')->name('app.master.recipes.store');
    Route::delete('/master/menus/{menu}/recipes/{recipe}', [InertiaMasterController::class, 'destroyRecipe'])->middleware('auth')->name('app.master.recipes.destroy');
    Route::get('/pos', [InertiaPosController::class, 'index'])->middleware('auth')->name('app.pos');
    Route::post('/pos/checkout', [InertiaPosController::class, 'checkout'])->middleware('auth')->name('app.pos.checkout');
    Route::get('/stock', [InertiaStockController::class, 'stock'])->middleware('auth')->name('app.stock');
    Route::get('/stock-transfers', [InertiaStockController::class, 'transfers'])->middleware('auth')->name('app.stock.transfers');
    Route::post('/stock-transfers', [InertiaStockController::class, 'storeTransfer'])->middleware('auth')->name('app.stock.transfers.store');
    Route::post('/stock-transfers/{id}/{action}', [InertiaStockController::class, 'transferAction'])->middleware('auth')->name('app.stock.transfers.action');
    Route::get('/stock-opnames', [InertiaStockController::class, 'opnames'])->middleware('auth')->name('app.stock.opnames');
    Route::post('/stock-opnames/{id}/apply', [InertiaStockController::class, 'applyOpname'])->middleware('auth')->name('app.stock.opnames.apply');
    Route::post('/stock-opnames', [InertiaStockController::class, 'storeOpname'])->middleware('auth')->name('app.stock.opnames.store');
    Route::get('/transactions', [InertiaTransactionController::class, 'index'])->middleware('auth')->name('app.transactions');
    Route::post('/transactions/{id}/void', [InertiaTransactionController::class, 'void'])->middleware('auth')->name('app.transactions.void');
    Route::get('/settings/branding', [InertiaSettingsController::class, 'branding'])->middleware('auth')->name('app.settings.branding');
    Route::post('/settings/branding', [InertiaSettingsController::class, 'saveBranding'])->middleware('auth')->name('app.settings.branding.save');
    Route::get('/settings/permissions', [InertiaSettingsController::class, 'permissions'])->middleware('auth')->name('app.settings.permissions');
    Route::get('/settings/permissions/{role}', [InertiaSettingsController::class, 'permissionData'])->middleware('auth')->name('app.settings.permissions.data');
    Route::post('/settings/permissions', [InertiaSettingsController::class, 'savePermissions'])->middleware('auth')->name('app.settings.permissions.save');
    Route::post('/settings/permissions/reset', [InertiaSettingsController::class, 'resetPermissions'])->middleware('auth')->name('app.settings.permissions.reset');
    Route::get('/operations/{mode}', [InertiaOperationsController::class, 'index'])->middleware('auth')->name('app.operations');
    Route::post('/operations/payrolls', [InertiaOperationsController::class, 'storePayroll'])->middleware('auth')->name('app.operations.payrolls.store');
    Route::post('/operations/kasbons', [InertiaOperationsController::class, 'storeKasbon'])->middleware('auth')->name('app.operations.kasbons.store');
    Route::post('/operations/kasbons/{id}/{action}', [InertiaOperationsController::class, 'kasbonAction'])->middleware('auth')->name('app.operations.kasbons.action');
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

Route::prefix('pos')
    ->middleware('auth')
    ->group(function () {
        Route::post('/outlet-context', function (Request $request) {
            $data = $request->validate([
                'selected_outlet_id' => ['nullable', 'integer'],
            ]);

            OutletContext::setCurrentOutletId($data['selected_outlet_id'] ?? null);

            return back();
        })->name('pos.outlet-context.update');
    });

Route::middleware('auth')->get('/receipt/{record}', function (SalesTransaction $record) {
    $user = OutletContext::user();
    if ($user && ! $user->isAdmin() && $record->outlet_id !== $user->outlet_id) {
        abort(403);
    }
    $record->load(['outlet', 'cashier', 'items.menuItem']);

    return view('receipt', ['transaction' => $record]);
})->name('receipt.show');

Route::get('/manual', fn () => view('manual'))->name('manual');
Route::get('/manual/download-doc', function () {
    $html = view('manual')->render();
    $doc = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><style>body{font-family:Calibri, sans-serif;}</style></head><body>".$html.'</body></html>';

    return response($doc, 200, [
        'Content-Type' => 'application/msword',
        'Content-Disposition' => 'attachment; filename="USER_MANUAL_SEDIA.doc"',
    ]);
})->name('manual.doc');
Route::get('/manual/download-pdf', function () {
    return redirect('/manual');
})->name('manual.pdf');
