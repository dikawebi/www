<?php

namespace App\Http\Controllers;
use App\Exceptions\InsufficientStockException;

use App\Models\Employee;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\SalesTransaction;
use App\Support\OutletContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InertiaPosController extends Controller
{
    public function index(): Response
    {
        $user = OutletContext::user();
        $outletId = OutletContext::currentOutletId() ?? $user?->outlet_id;

        return Inertia::render('Pos/Index', [
            'menus' => MenuItem::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get(['id', 'name', 'category', 'price']),
            'outlets' => OutletContext::selectableOutletOptions(),
            'selectedOutletId' => $outletId,
            'defaultOutletId' => $outletId ?? Outlet::query()->where('is_active', true)->value('id'),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $user = OutletContext::user();
        $data = $request->validate([
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'distinct', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $outletId = $user?->isAdmin() ? ($data['outlet_id'] ?? OutletContext::currentOutletId()) : $user?->outlet_id;
        if (! $outletId || ! OutletContext::selectableOutlets()->whereKey($outletId)->exists()) {
            throw ValidationException::withMessages(['outlet_id' => 'Pilih outlet yang valid sebelum checkout.']);
        }

        $menus = MenuItem::query()->whereIn('id', collect($data['items'])->pluck('menu_item_id'))->where('is_active', true)->get()->keyBy('id');
        if ($menus->count() !== count($data['items'])) {
            throw ValidationException::withMessages(['items' => 'Salah satu menu tidak tersedia atau sudah dinonaktifkan.']);
        }
        $total = collect($data['items'])->sum(fn ($item) => (float) $menus->get($item['menu_item_id'])->price * $item['quantity']);
        if ((float) $data['paid_amount'] < $total) {
            throw ValidationException::withMessages(['paid_amount' => 'Jumlah pembayaran kurang dari total transaksi.']);
        }

        $cashierId = Employee::query()->where('outlet_id', $outletId)->where('status', 'active')->where('user_id', $user?->id)->value('id')
            ?? Employee::query()->where('outlet_id', $outletId)->where('status', 'active')->value('id');

        try {
            $transaction = DB::transaction(function () use ($data, $menus, $outletId, $cashierId, $total) {
                $transaction = SalesTransaction::create([
                'invoice_number' => 'INV-'.now()->format('Ymd-His').'-'.strtoupper(Str::random(4)),
                'outlet_id' => $outletId,
                'cashier_id' => $cashierId,
                'transaction_date' => now(),
                'total_amount' => 0,
                'payment_method' => $data['payment_method'],
                'payments' => [['method' => $data['payment_method'], 'amount' => (float) $data['paid_amount']]],
                'paid_amount' => $data['paid_amount'],
                'change_amount' => (float) $data['paid_amount'] - $total,
                'status' => 'completed',
            ]);

                foreach ($data['items'] as $item) {
                    $menu = $menus->get($item['menu_item_id']);
                    $transaction->items()->create([
                    'menu_item_id' => $menu->id,
                    'quantity' => $item['quantity'],
                    'price' => $menu->price,
                    'subtotal' => (float) $menu->price * $item['quantity'],
                    ]);
                }

                return $transaction;
            });
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages([
                'checkout' => sprintf(
                    'Stok %s tidak cukup di %s. Tersedia %s, dibutuhkan %s.',
                    $exception->ingredient->name,
                    $exception->outlet->name,
                    $exception->formattedAvailable(),
                    $exception->formattedRequired(),
                ),
            ]);
        }

        return back()->with('success', 'Transaksi '.$transaction->invoice_number.' berhasil dibuat.');
    }
}
