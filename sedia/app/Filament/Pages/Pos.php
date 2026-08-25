<?php

namespace App\Filament\Pages;

use App\Exceptions\InsufficientStockException;
use App\Models\Employee;
use App\Models\MenuItem;
use App\Models\SalesTransaction;
use App\Support\OutletContext;
use BackedEnum;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

class Pos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'POS Kasir';

    protected static ?string $title = 'POS Kasir';

    protected string $view = 'filament.pages.pos';

    public ?int $outletId = null;

    public string $paymentMethod = 'cash';

    public string $search = '';

    public string $selectedCategory = '';

    /** @var array<int, array{menu_item_id: int, name: string, price: float, qty: int, subtotal: float}> */
    public array $cart = [];

    public function mount(): void
    {
        $user = OutletContext::user();
        $this->outletId = $user && ! $user->isAdmin() ? $user->outlet_id : (OutletContext::currentOutletId() ?? $user?->outlet_id);
        $this->paymentMethod = 'cash';
    }

    public function outletOptions(): array
    {
        return OutletContext::selectableOutletOptions();
    }

    public function isAdminUser(): bool
    {
        return OutletContext::user()?->isAdmin() ?? false;
    }

    /** @return Collection<int, string> */
    public function getCategoriesProperty(): Collection
    {
        return MenuItem::where('is_active', true)->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category');
    }

    /** @return Collection<int, MenuItem> */
    public function getMenuItemsProperty(): Collection
    {
        $q = MenuItem::where('is_active', true)->orderBy('name');
        if (filled($this->search)) {
            $q->where(function ($qq) {
                $qq->where('name', 'like', '%'.$this->search.'%')->orWhere('category', 'like', '%'.$this->search.'%');
            });
        }
        if (filled($this->selectedCategory)) {
            $q->where('category', $this->selectedCategory);
        }

        return $q->get();
    }

    public function getCartTotalProperty(): float
    {
        return (float) collect($this->cart)->sum('subtotal');
    }

    public function addToCart(int $menuItemId): void
    {
        $menu = MenuItem::find($menuItemId);
        if (! $menu) {
            return;
        }
        foreach ($this->cart as $i => $row) {
            if ($row['menu_item_id'] === $menuItemId) {
                $this->cart[$i]['qty']++;
                $this->cart[$i]['subtotal'] = $this->cart[$i]['qty'] * $this->cart[$i]['price'];

                return;
            }
        }
        $this->cart[] = [
            'menu_item_id' => $menu->id,
            'name' => $menu->name,
            'price' => (float) $menu->price,
            'qty' => 1,
            'subtotal' => (float) $menu->price,
        ];
    }

    public function decQty(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }
        $this->cart[$index]['qty']--;
        if ($this->cart[$index]['qty'] <= 0) {
            array_splice($this->cart, $index, 1);
        } else {
            $this->cart[$index]['subtotal'] = $this->cart[$index]['qty'] * $this->cart[$index]['price'];
        }
    }

    public function incQty(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }
        $this->cart[$index]['qty']++;
        $this->cart[$index]['subtotal'] = $this->cart[$index]['qty'] * $this->cart[$index]['price'];
    }

    public function removeFromCart(int $index): void
    {
        array_splice($this->cart, $index, 1);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function checkout(): void
    {
        if (empty($this->cart)) {
            FilamentNotification::make()->title('Keranjang kosong')->warning()->send();

            return;
        }
        $outletId = $this->outletId;
        if (! $outletId) {
            FilamentNotification::make()->title('Pilih outlet dulu')->danger()->send();

            return;
        }
        // Validasi outlet untuk staff
        if (! $this->isAdminUser() && $outletId !== OutletContext::user()?->outlet_id) {
            FilamentNotification::make()->title('Outlet tidak valid')->danger()->send();

            return;
        }

        $cashierId = Employee::where('outlet_id', $outletId)->where('status', 'active')->value('id');

        try {
            $trx = null;
            DB::transaction(function () use (&$trx, $outletId, $cashierId) {
                $trx = SalesTransaction::create([
                    'invoice_number' => 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),
                    'outlet_id' => $outletId,
                    'cashier_id' => $cashierId,
                    'transaction_date' => now(),
                    'total_amount' => 0,
                    'payment_method' => $this->paymentMethod,
                    'status' => 'completed',
                ]);
                foreach ($this->cart as $row) {
                    $trx->items()->create([
                        'menu_item_id' => $row['menu_item_id'],
                        'quantity' => $row['qty'],
                        'price' => $row['price'],
                        'subtotal' => $row['subtotal'],
                    ]);
                }
            });

            $total = $this->cartTotal;
            $this->cart = [];
            FilamentNotification::make()->title('Transaksi berhasil')->body('Total Rp '.number_format($total, 0, ',', '.'))->success()->send();
            // Optional: redirect to receipt
            $this->dispatch('pos-checkout-success', invoice: $trx->invoice_number);
        } catch (InsufficientStockException $e) {
            FilamentNotification::make()
                ->danger()->title('Stok tidak cukup')
                ->body(($e->menuItemName ? $e->menuItemName.': ' : '').$e->getMessage())
                ->persistent()->send();
        } catch (\Throwable $e) {
            FilamentNotification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
        }
    }

    public function formatRupiah(float|int $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
