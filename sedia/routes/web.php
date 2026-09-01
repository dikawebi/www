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
