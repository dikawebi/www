<?php

namespace App\Filament\Pages;

use App\Exceptions\InsufficientStockException;
use App\Models\Employee;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\SalesTransaction;
use App\Support\OutletContext;
use App\Support\RolePermission;
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

    public static function canAccess(): bool
    {
        return RolePermission::can(OutletContext::user(), 'Pos', 'view');
    }

    public ?int $outletId = null;

    public string $paymentMethod = 'cash';

    public bool $isSplit = false;

    /** @var array<string, float> */
    public array $splitAmounts = ['cash' => 0, 'qris' => 0, 'transfer' => 0, 'debit' => 0];

    public ?float $cashReceived = null;

    public string $search = '';

    public string $selectedCategory = '';

    /** @var array<int, array{menu_item_id: int, name: string, price: float, qty: int, subtotal: float}> */
    public array $cart = [];

    /** @var array<int, array{id:int, name:string, category:string|null, price:float}> */
    public array $menuCache = [];

    public ?string $previewInvoice = null;

    public float $cartTotalLive = 0;

    public function mount(): void
    {
        $user = OutletContext::user();
        $this->outletId = OutletContext::currentOutletId() ?? ($user && ! $user->isAdmin() ? $user->outlet_id : Outlet::where('is_active', true)->value('id'));
        $this->paymentMethod = 'cash';
        $this->generatePreviewInvoice();
        $this->loadMenuCache();
        $this->syncCartTotalLive();
    }

    private function syncCartTotalLive(): void
    {
        $this->cartTotalLive = $this->cartTotal;
    }

    public function updatedOutletId(): void
    {
        if ($this->isAdminUser() && $this->outletId) {
            OutletContext::setCurrentOutletId($this->outletId);
        }
        $this->generatePreviewInvoice();
        $this->loadMenuCache();
    }

    public function generatePreviewInvoice(): void
    {
        $outletName = $this->outletId ? (Outlet::find($this->outletId)?->name ?? 'POS') : 'POS';
        $cleanName = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $outletName));
        $this->previewInvoice = 'INV/'.$cleanName.'/'.now()->format('Ymd/His');
    }

    public function updatedSearch(): void
    {
        $this->loadMenuCache();
    }

    public function updatedSelectedCategory(): void
    {
        $this->loadMenuCache();
    }

    private function loadMenuCache(): void
    {
        $q = MenuItem::query()->select(['id', 'name', 'category', 'price'])->where('is_active', true)->orderBy('name');
        if (filled($this->search)) {
            $q->where(function ($qq) {
                $q2 = '%'.$this->search.'%';
                $qq->where('name', 'like', $q2)->orWhere('category', 'like', $q2);
            });
        }
        if (filled($this->selectedCategory)) {
            $q->where('category', $this->selectedCategory);
        }
        $this->menuCache = $q->get()->toArray();
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

    /** @return Collection<int, object> */
    public function getMenuItemsProperty(): Collection
    {
        // Pakai cache in-memory (menuCache) agar add-to-cart tidak query ulang.
        // Jika cache kosong (misal setelah dehydrate), fallback ke DB sekali.
        if (! empty($this->menuCache)) {
            return collect($this->menuCache)->map(fn ($a) => (object) $a);
        }

        $q = MenuItem::query()->select(['id', 'name', 'category', 'price'])->where('is_active', true)->orderBy('name');
        if (filled($this->search)) {
            $q->where(function ($qq) {
                $q2 = '%'.$this->search.'%';
                $qq->where('name', 'like', $q2)->orWhere('category', 'like', $q2);
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

    public function getPaidTotalProperty(): float
    {
        if (! $this->isSplit) {
            return $this->cartTotal;
        }

        return (float) array_sum(array_map(fn ($v) => (float) $v, $this->splitAmounts));
    }

    public function getChangeDueProperty(): float
    {
        return max(0, $this->paidTotal - $this->cartTotal);
    }

    public function getCashChangeProperty(): float
    {
        return max(0, (float) ($this->cashReceived ?? 0) - $this->cartTotal);
    }

    public function updatedIsSplit(): void
    {
        if ($this->isSplit) {
            $this->splitAmounts = ['cash' => $this->cartTotal, 'qris' => 0, 'transfer' => 0, 'debit' => 0];
        }
    }

    public function addToCart(int $menuItemId): void
    {
        // Cari di cache dulu (tanpa DB) agar respon tap langsung.
        $cached = collect($this->menuCache)->firstWhere('id', $menuItemId);
        if ($cached) {
            $id = (int) $cached['id'];
            $name = (string) $cached['name'];
            $price = (float) $cached['price'];
        } else {
            $menu = MenuItem::find($menuItemId);
            if (! $menu) {
                return;
            }
            $id = $menu->id;
            $name = $menu->name;
            $price = (float) $menu->price;
        }

        foreach ($this->cart as $i => $row) {
            if ($row['menu_item_id'] === $id) {
                $this->cart[$i]['qty']++;
                $this->cart[$i]['subtotal'] = $this->cart[$i]['qty'] * $this->cart[$i]['price'];
                $this->syncCartTotalLive();

                return;
            }
        }
        $this->cart[] = [
            'menu_item_id' => $id,
            'name' => $name,
            'price' => $price,
            'qty' => 1,
            'subtotal' => $price,
        ];
        $this->syncCartTotalLive();
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
        $this->syncCartTotalLive();
    }

    public function incQty(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }
        $this->cart[$index]['qty']++;
        $this->cart[$index]['subtotal'] = $this->cart[$index]['qty'] * $this->cart[$index]['price'];
        $this->syncCartTotalLive();
    }

    public function removeFromCart(int $index): void
    {
        array_splice($this->cart, $index, 1);
        $this->syncCartTotalLive();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->syncCartTotalLive();
    }

    public function resetPos(): void
    {
        $this->cart = [];
        $this->cashReceived = null;
        $this->syncCartTotalLive();
        $this->generatePreviewInvoice();
    }

    public function checkout(): void
    {
        if (empty($this->cart)) {
            FilamentNotification::make()->title('Keranjang kosong')->warning()->send();

            return;
        }
        $outletId = $this->outletId ?? OutletContext::currentOutletId();
        if (! $outletId) {
            $outletId = Outlet::where('is_active', true)->value('id');
        }

        if (! $outletId) {
            FilamentNotification::make()->title('Pilih outlet dulu')->danger()->send();

            return;
        }
        // Validasi outlet untuk staff
        if (! $this->isAdminUser() && $outletId !== OutletContext::user()?->outlet_id) {
            FilamentNotification::make()->title('Outlet tidak valid')->danger()->send();

            return;
        }

        $total = $this->cartTotal;

        // Validasi khusus cash: wajib input uang diterima via modal
        if (! $this->isSplit && $this->paymentMethod === 'cash') {
            if ($this->cashReceived === null || $this->cashReceived == 0) {
                FilamentNotification::make()->title('Masukkan uang diterima')->body('Total '.$this->formatRupiah($total))->warning()->send();

                return;
            }
            if ((float) $this->cashReceived < $total - 0.01) {
                FilamentNotification::make()->title('Uang kurang')->body('Total '.$this->formatRupiah($total).', diterima '.$this->formatRupiah((float) $this->cashReceived))->danger()->send();

                return;
            }
        }

        if ($this->isSplit) {
            $paid = $this->paidTotal;
            $change = $this->changeDue;
            if ($paid < $total - 0.01) {
                FilamentNotification::make()->title('Bayar kurang')->body('Total '.$this->formatRupiah($total).', dibayar '.$this->formatRupiah($paid))->danger()->send();

                return;
            }
            $payments = collect($this->splitAmounts)->filter(fn ($v) => $v > 0)->map(fn ($v, $k) => ['method' => $k, 'amount' => (float) $v])->values()->all();
            $primaryMethod = collect($payments)->sortByDesc('amount')->first()['method'] ?? 'cash';
        } elseif ($this->paymentMethod === 'cash') {
            $paid = (float) $this->cashReceived;
            $change = max(0, $paid - $total);
            $payments = [['method' => 'cash', 'amount' => $paid]];
            $primaryMethod = 'cash';
        } else {
            $payments = null;
            $paid = $total;
            $change = 0;
            $primaryMethod = $this->paymentMethod;
        }

        $cashierId = Employee::where('outlet_id', $outletId)->where('status', 'active')->value('id');

        try {
            $trx = null;
            $invoiceNumber = $this->previewInvoice ?? ('INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)));
            DB::transaction(function () use (&$trx, $outletId, $cashierId, $payments, $paid, $change, $primaryMethod, $invoiceNumber) {
                $trx = SalesTransaction::create([
                    'invoice_number' => $invoiceNumber,
                    'outlet_id' => $outletId,
                    'cashier_id' => $cashierId,
                    'transaction_date' => now(),
                    'total_amount' => 0,
                    'payment_method' => $primaryMethod,
                    'payments' => $payments,
                    'paid_amount' => $paid,
                    'change_amount' => $change,
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
            $paidForNote = $paid;
            $changeForNote = $change;
            $this->cart = [];
            $this->cashReceived = null;
            $this->syncCartTotalLive();
            $this->generatePreviewInvoice();
            $msg = $primaryMethod === 'cash' && $changeForNote > 0
                ? 'Dibayar '.number_format($paidForNote, 0, ',', '.').' • Kembalian '.number_format($changeForNote, 0, ',', '.')
                : 'Total Rp '.number_format($total, 0, ',', '.');
            FilamentNotification::make()->title('Transaksi berhasil')->body($msg)->success()->send();
            $this->dispatch('pos-checkout-success', invoice: $trx?->invoice_number ?? '', receiptUrl: route('receipt.show', $trx?->id ?? 0));
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
