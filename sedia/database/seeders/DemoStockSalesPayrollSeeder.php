<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\Payroll;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\User;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoStockSalesPayrollSeeder extends Seeder
{
    /**
     * Seeder komprehensif untuk demo:
     *  - Stok awal per outlet & bahan (via StockService::recordMovement)
     *  - Transaksi penjualan 30 hari terakhir (dengan potong stok otomatis)
     *  - Master gaji: EmployeeTransaction (kasbon/bonus) + Payroll 3 bulan
     *
     * Aman di-run berulang: akan skip jika data demo sudah ada.
     */
    public function run(): void
    {
        // Tanpa outer transaction — biarkan setiap movement commit sendiri agar stok terbaca saat penjualan
        $outlets = Outlet::whereIn('name', ['Sempu', 'Kasuari'])->get();
        if ($outlets->isEmpty()) {
            $outlets = Outlet::all();
        }
        $ingredients = Ingredient::where('is_active', true)->get();
        $menus = MenuItem::where('is_active', true)->get();
        $employees = Employee::with('outlet')->get();
        $admin = User::where('role', 'admin')->first();
        $stockService = app(StockService::class);

        if ($ingredients->isEmpty() || $menus->isEmpty() || $outlets->isEmpty()) {
            $this->command?->warn('DemoStockSalesPayrollSeeder: outlet/ingredient/menu kosong, jalankan DkriukSeeder dulu.');

            return;
        }

        // -------------------------------------------------
        // 1) STOK: isi awal per outlet 250-350 pcs (besar biar tidak habis 30 hari)
        // -------------------------------------------------
        $this->command?->info('Seeding stock...');
        foreach ($outlets as $outlet) {
            foreach ($ingredients as $ing) {
                $existing = DB::table('stocks')->where('outlet_id', $outlet->id)->where('ingredient_id', $ing->id)->value('quantity');
                if ($existing !== null && (float) $existing >= 120) {
                    continue;
                }
                $qty = fake()->numberBetween(280, 380);
                $stockService->recordMovement(
                    outlet: $outlet,
                    ingredient: $ing,
                    type: StockMovementType::Purchase,
                    quantity: $qty,
                    createdBy: $admin?->id,
                    note: 'Stok awal demo - '.$ing->name,
                );
            }
            // Top-up acak 2 bahan
            foreach (collect($ingredients)->random(min(2, $ingredients->count())) as $midIng) {
                $stockService->recordMovement(
                    outlet: $outlet,
                    ingredient: $midIng,
                    type: StockMovementType::Purchase,
                    quantity: fake()->numberBetween(80, 140),
                    createdBy: $admin?->id,
                    note: 'Restock demo tengah bulan',
                );
            }
        }

        // -------------------------------------------------
        // 2) MASTER GAJI: Kasbon/bonus + Payroll 3 bulan
        // -------------------------------------------------
        $this->command?->info('Seeding payroll master...');

        // Update base_salary jika masih 0 (bawaan DkriukSeeder)
        foreach ($employees as $emp) {
            if ((float) $emp->base_salary === 0.0) {
                $emp->update(['base_salary' => fake()->randomElement([1500000, 1800000, 2000000, 2200000])]);
            }
        }

        // Konvensi gajian 21-20: periode 21 Mei → 20 Juni, gajian tgl 22 Juni
        $periods = [
            ['start' => '2026-05-21', 'end' => '2026-06-20', 'pay_date' => '2026-06-22'],
            ['start' => '2026-06-21', 'end' => '2026-07-20', 'pay_date' => '2026-07-22'],
            ['start' => '2026-07-21', 'end' => '2026-08-20', 'pay_date' => '2026-08-22'],
            ['start' => '2026-08-21', 'end' => '2026-09-20', 'pay_date' => '2026-09-22'],
        ];

        foreach ($employees as $emp) {
            // Kasbon / bonus transaksi (2-4 per karyawan)
            $existingTx = EmployeeTransaction::where('employee_id', $emp->id)->count();
            if ($existingTx < 2) {
                EmployeeTransaction::create([
                    'employee_id' => $emp->id,
                    'outlet_id' => $emp->outlet_id,
                    'type' => 'kasbon',
                    'amount' => fake()->randomElement([100000, 150000, 200000, 250000]),
                    'trans_date' => fake()->dateTimeBetween('-2 months', '-1 week')->format('Y-m-d'),
                    'status' => 'approved',
                    'note' => 'Kasbon demo - keperluan keluarga',
                ]);
                EmployeeTransaction::create([
                    'employee_id' => $emp->id,
                    'outlet_id' => $emp->outlet_id,
                    'type' => 'bonus',
                    'amount' => fake()->randomElement([50000, 75000, 100000, 150000]),
                    'trans_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
                    'status' => 'approved',
                    'note' => 'Bonus kehadiran demo',
                ]);
            }

            foreach ($periods as $period) {
                $start = $period['start'];
                $end = $period['end'];
                $payDate = $period['pay_date'];

                $exists = Payroll::where('employee_id', $emp->id)
                    ->where('period_start', $start)
                    ->exists();
                if ($exists) {
                    continue;
                }

                // Potongan kasbon di periode ini = kasbon antara period_start & period_end
                $kasbonInPeriod = EmployeeTransaction::where('employee_id', $emp->id)
                    ->where('type', 'kasbon')
                    ->whereBetween('trans_date', [$start, $end])
                    ->where('status', 'approved')
                    ->sum('amount');

                // Fallback kalau tidak ada kasbon di bulan itu, isi random potongan kecil
                if ((float) $kasbonInPeriod === 0.0 && fake()->boolean(30)) {
                    $kasbonInPeriod = fake()->randomElement([0, 50000, 100000]);
                }

                $periodLabel = Carbon::parse($start)->format('d M Y').' — '.Carbon::parse($end)->format('d M Y');

                Payroll::create([
                    'outlet_id' => $emp->outlet_id,
                    'employee_id' => $emp->id,
                    'pay_date' => $payDate,
                    'period_start' => $start,
                    'period_end' => $end,
                    'base_salary' => $emp->base_salary,
                    'bonus_masuk' => fake()->numberBetween(0, 8) * 15000,
                    'bonus_goreng' => fake()->numberBetween(0, 10) * 10000,
                    'kasbon_deduction' => $kasbonInPeriod,
                    'status' => Carbon::parse($payDate)->isBefore(now()) ? 'paid' : fake()->randomElement(['draft', 'paid']),
                    'note' => 'Payroll demo '.$periodLabel.' — '.$emp->name,
                ]);
            }
        }

        // -------------------------------------------------
        // 3) TRANSAKSI PENJUALAN: 30 hari terakhir
        // -------------------------------------------------
        $this->command?->info('Seeding sales transactions...');

        // Guard idempotent: jika sudah ada > 80 transaksi demo, skip
        if (SalesTransaction::count() > 80) {
            $this->command?->warn('Sales sudah ada >80, skip seeding penjualan (hapus manual jika ingin regenerate).');

            return;
        }

        // Pastikan menu punya harga demo (DkriukSeeder set 0 placeholder)
        foreach ($menus as $menu) {
            if ((float) $menu->price == 0) {
                $menu->update(['price' => fake()->randomElement([15000, 18000, 20000, 23000, 26000, 28000])]);
            }
        }
        $menus = MenuItem::where('is_active', true)->get();

        $paymentMethods = ['cash', 'qris', 'transfer', 'debit'];

        for ($d = 29; $d >= 0; $d--) {
            $date = Carbon::now()->subDays($d);
            $isWeekend = $date->isWeekend();

            foreach ($outlets as $outlet) {
                $cashier = $employees->where('outlet_id', $outlet->id)->first();
                if (! $cashier) {
                    continue;
                }

                $txPerDay = $isWeekend ? fake()->numberBetween(6, 12) : fake()->numberBetween(3, 8);

                for ($n = 0; $n < $txPerDay; $n++) {
                    $hour = fake()->numberBetween(9, 21);
                    $minute = fake()->numberBetween(0, 59);
                    $txTime = $date->copy()->setTime($hour, $minute);
                    $itemsCount = fake()->numberBetween(1, 3);
                    $pickedMenus = $menus->random(min($itemsCount, $menus->count()));
                    $invoice = 'INV/'.$outlet->name.'/'.$txTime->format('Ymd').'/'.str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT).'-'.fake()->bothify('??##');
                    $paymentMethod = fake()->randomElement($paymentMethods);
                    $isCompleted = fake()->boolean(96);

                    $tx = SalesTransaction::create([
                        'invoice_number' => $invoice,
                        'outlet_id' => $outlet->id,
                        'cashier_id' => $cashier->id,
                        'transaction_date' => $txTime,
                        'total_amount' => 0,
                        'payment_method' => $paymentMethod,
                        'payments' => [['method' => $paymentMethod, 'amount' => 0]],
                        'paid_amount' => 0,
                        'change_amount' => 0,
                        'status' => $isCompleted ? 'completed' : 'void',
                    ]);

                    // Items: stok dipotong otomatis oleh SalesTransactionItemObserver::created
                    // Jika stok habis, restock ingredient lalu retry 1x
                    foreach ($pickedMenus as $menu) {
                        $qty = fake()->numberBetween(1, 3);
                        $attempt = 0;
                        retryItem:
                        try {
                            SalesTransactionItem::create([
                                'sales_transaction_id' => $tx->id,
                                'menu_item_id' => $menu->id,
                                'quantity' => $qty,
                                // price/subtotal akan di-override observer dari MenuItem::price (jika 0 → observer pakai 0, tapi kita set harga demo)
                                'price' => (float) ($menu->price > 0 ? $menu->price : fake()->randomElement([13000, 15000, 18000, 22000, 25000])),
                                'subtotal' => 0,
                            ]);
                        } catch (InsufficientStockException $e) {
                            if ($attempt >= 1) {
                                throw $e;
                            }
                            $attempt++;
                            // Restock ingredient yang kurang 150 pcs
                            $neededIngredient = $e->ingredient ?? $menu->recipes->first()?->ingredient;
                            if ($neededIngredient) {
                                $stockService->recordMovement(
                                    outlet: $outlet,
                                    ingredient: $neededIngredient,
                                    type: StockMovementType::Purchase,
                                    quantity: 150,
                                    createdBy: $admin?->id,
                                    note: 'Auto restock seeder (habis) - '.$neededIngredient->name,
                                );
                            } else {
                                // Fallback: restock semua resep menu ini
                                $menu->loadMissing('recipes.ingredient');
                                foreach ($menu->recipes as $recipe) {
                                    $stockService->recordMovement(outlet: $outlet, ingredient: $recipe->ingredient, type: StockMovementType::Purchase, quantity: 120, createdBy: $admin?->id, note: 'Auto restock seeder');
                                }
                            }
                            goto retryItem;
                        }
                    }

                    // Update payments agar sinkron (total sudah di-recalculate observer)
                    $tx->refresh();
                    $total = (float) $tx->total_amount;
                    if ($total > 0) {
                        $tx->update([
                            'payments' => [['method' => $paymentMethod, 'amount' => $total]],
                            'paid_amount' => $total,
                            'change_amount' => 0,
                        ]);
                    }
                }
            }
        }

        $this->command?->info('Demo seeder selesai: '.SalesTransaction::count().' transaksi + '.Payroll::count().' payroll + '.DB::table('stocks')->count().' stock rows.');
    }
}
