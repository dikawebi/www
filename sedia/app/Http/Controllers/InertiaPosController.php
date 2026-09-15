<?php

namespace App\Http\Controllers;
use App\Exceptions\InsufficientStockException;

use App\Models\Employee;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Support\OutletContext;
use App\Support\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InertiaPosController extends Controller
{
    public function index(Request $request): Response
    {
        $user = OutletContext::user();
        abort_unless(RolePermission::can($user, 'Pos', 'view'), 403);
        $requestedOutlet = $request->integer('outlet_id');
        $outletId = $user?->isAdmin() && $requestedOutlet ? $requestedOutlet : (OutletContext::currentOutletId() ?? $user?->outlet_id);
        if ($outletId && ! OutletContext::selectableOutlets()->whereKey($outletId)->exists()) abort(403);
        $stock = Stock::query()->where('outlet_id', $outletId)->pluck('quantity', 'ingredient_id');
        $menus = MenuItem::query()->where('is_active', true)->with('recipes:id,menu_item_id,ingredient_id,qty_per_unit')->orderBy('category')->orderBy('name')->get()->map(function (MenuItem $menu) use ($stock) {
            $available = $menu->recipes->isEmpty() ? null : $menu->recipes->map(fn ($recipe) => (int) floor((float) ($stock[$recipe->ingredient_id] ?? 0) / (float) $recipe->qty_per_unit))->min();
            return ['id' => $menu->id, 'name' => $menu->name, 'category' => $menu->category, 'barcode' => $menu->barcode, 'price' => $menu->price, 'available_quantity' => $available];
        });

        return Inertia::render('Pos/Index', [
            'menus' => $menus,
            'outlets' => OutletContext::selectableOutletOptions(),
            'selectedOutletId' => $outletId,
            'defaultOutletId' => $outletId ?? Outlet::query()->where('is_active', true)->value('id'),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $user = OutletContext::user();
        abort_unless(RolePermission::can($user, 'Pos', 'create'), 403);
        $data = $request->validate([
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'checkout_token' => ['required', 'uuid'],
            'payment_method' => ['required', 'in:cash,qris,transfer'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:cash,qris,transfer'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'distinct', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $outletId = $user?->isAdmin() ? ($data['outlet_id'] ?? OutletContext::currentOutletId()) : $user?->outlet_id;
        if (! $outletId || ! OutletContext::selectableOutlets()->whereKey($outletId)->exists()) {
            throw ValidationException::withMessages(['outlet_id' => 'Pilih outlet yang valid sebelum checkout.']);
        }

        $payments = collect($data['payments'] ?? [['method' => $data['payment_method'], 'amount' => (float) $data['paid_amount']]])
            ->map(fn ($payment) => ['method' => $payment['method'], 'amount' => (float) $payment['amount']])
            ->values();

        $existing = SalesTransaction::query()->where('checkout_token', $data['checkout_token'])->first();
        if ($existing) {
            abort_unless((int) $existing->created_by === (int) $user->id, 403);
            return back()->with('success', 'Transaksi '.$existing->invoice_number.' sudah tercatat.')->with('receipt_url', route('receipt.show', $existing));
        }

        try {
            $transaction = DB::transaction(function () use ($data, $outletId, $user, $payments) {
                DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
                $existing = SalesTransaction::query()->where('checkout_token', $data['checkout_token'])->first();
                if ($existing) return $existing;
                $menus = MenuItem::query()->whereIn('id', collect($data['items'])->pluck('menu_item_id'))->where('is_active', true)->lockForUpdate()->get()->keyBy('id');
                if ($menus->count() !== count($data['items'])) throw ValidationException::withMessages(['items' => 'Salah satu menu tidak tersedia atau sudah dinonaktifkan.']);
                $subtotal = collect($data['items'])->sum(fn ($item) => (float) $menus->get($item['menu_item_id'])->price * $item['quantity']);
                $discount = min((float) ($data['discount_amount'] ?? 0), $subtotal);
                $total = $subtotal - $discount;
                $paidAmount = (float) $payments->sum('amount');
                if ($paidAmount < $total) throw ValidationException::withMessages(['paid_amount' => 'Jumlah pembayaran kurang dari total transaksi.']);
                if ($paidAmount > $total && ! $payments->contains('method', 'cash')) throw ValidationException::withMessages(['payments' => 'Pembayaran non-tunai harus sesuai total transaksi.']);
                if (($paidAmount - $total) > (float) $payments->where('method', 'cash')->sum('amount')) throw ValidationException::withMessages(['payments' => 'Kembalian tidak boleh melebihi pembayaran tunai.']);
                $cashierId = Employee::query()->where('outlet_id', $outletId)->where('status', 'active')->where('user_id', $user->id)->value('id')
                    ?? Employee::query()->where('outlet_id', $outletId)->where('status', 'active')->value('id');
                $transaction = SalesTransaction::create([
                'invoice_number' => 'INV-'.now()->format('Ymd-His').'-'.strtoupper(Str::random(4)),
                'checkout_token' => $data['checkout_token'],
                'created_by' => $user->id,
                'outlet_id' => $outletId,
                'cashier_id' => $cashierId,
                'transaction_date' => now(),
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'payment_method' => $payments->count() > 1 ? 'split' : $payments->first()['method'],
                'payments' => $payments->all(),
                'paid_amount' => $paidAmount,
                'change_amount' => $paidAmount - $total,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
            ]);

                foreach ($data['items'] as $item) {
                    $menu = $menus->get($item['menu_item_id']);
                    $transaction->items()->create([
                    'menu_item_id' => $menu->id,
                    'quantity' => $item['quantity'],
                    'price' => $menu->price,
                    'subtotal' => (float) $menu->price * $item['quantity'],
                    'notes' => $item['notes'] ?? null,
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

        return back()->with('success', 'Transaksi '.$transaction->invoice_number.' berhasil dibuat.')->with('receipt_url', route('receipt.show', $transaction));
    }
}
