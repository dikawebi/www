<?php

namespace App\Http\Controllers;

use App\Models\SalesTransaction;
use App\Services\StockService;
use App\Support\OutletContext;
use App\Support\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class InertiaTransactionController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(RolePermission::can(OutletContext::user(), 'SalesTransactionResource', 'view'), 403);
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:completed,void'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
        $rows = OutletContext::visibleQuery(SalesTransaction::query())
            ->with(['outlet:id,name', 'cashier:id,name', 'items.menuItem:id,name'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->string('q') . '%';
                $query->where(function ($q) use ($term) { $q->where('invoice_number', 'like', $term)->orWhereHas('cashier', fn ($cashier) => $cashier->where('name', 'like', $term))->orWhereHas('outlet', fn ($outlet) => $outlet->where('name', 'like', $term)); });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('transaction_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('transaction_date', '<=', $request->date('date_to')))
            ->latest('transaction_date')->paginate(25)->through(fn (SalesTransaction $transaction) => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'outlet_name' => $transaction->outlet?->name,
                'cashier_name' => $transaction->cashier?->name,
                'transaction_date' => $transaction->transaction_date?->format('Y-m-d H:i'),
                'total_amount' => (float) $transaction->total_amount,
                'payment_method' => $transaction->payment_method,
                'status' => $transaction->status,
            ]);

        return Inertia::render('Transactions/Index', ['rows' => $rows, 'filters' => $request->only('q', 'status', 'date_from', 'date_to')]);
    }

    public function void(int $id, StockService $service): RedirectResponse
    {
        abort_unless(OutletContext::user()?->isAdmin() && RolePermission::can(OutletContext::user(), 'SalesTransactionResource', 'edit'), 403);
        try {
            $service->voidSale(SalesTransaction::query()->findOrFail($id), OutletContext::user()->id);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['transaction' => $exception->getMessage()]);
        }

        return back()->with('success', 'Transaksi berhasil di-void.');
    }
}
