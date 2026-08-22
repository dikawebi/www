<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class SalesAndStockSeeder extends Seeder
{
    public function run(): void
    {
        $stockist = Outlet::where('name', 'like', '%Stockist%')->first();
        $outletSudirman = Outlet::where('name', 'like', '%Sudirman%')->first();
        $outletBandung = Outlet::where('name', 'like', '%Bandung%')->first();

        $admin = User::where('role', 'admin')->first();

        $emp2 = Employee::where('name', 'Siti Aminah')->first();
        $emp3 = Employee::where('name', 'Asep Kurnia')->first();

        $ingKopi = Ingredient::where('name', 'like', '%Arabika%')->first();
        $ingSusu = Ingredient::where('name', 'like', '%Susu%')->first();
        $ingGula = Ingredient::where('name', 'like', '%Gula%')->first();
        $ingCup = Ingredient::where('name', 'like', '%Cup%')->first();
        $ingStraw = Ingredient::where('name', 'like', '%Sedotan%')->first();

        $menuEsKopi = MenuItem::where('name', 'like', '%Aren%')->first();
        $menuLatte = MenuItem::where('name', 'like', '%Latte%')->first();
        $menuEspresso = MenuItem::where('name', 'like', '%Espresso%')->first();

        $stockService = app(StockService::class);
        $ingredients = [$ingKopi, $ingSusu, $ingGula, $ingCup, $ingStraw];

        foreach ($ingredients as $ing) {
            $stockService->recordMovement(
                outlet: $stockist,
                ingredient: $ing,
                type: \App\Enums\StockMovementType::Purchase,
                quantity: 500,
                createdBy: $admin->id,
                note: 'Stok awal Gudang Pusat'
            );
        }

        $stockService->recordMovement(outlet: $outletSudirman, ingredient: $ingKopi, type: \App\Enums\StockMovementType::Purchase, quantity: 15, createdBy: $admin->id, note: 'Stok awal');
        $stockService->recordMovement(outlet: $outletSudirman, ingredient: $ingSusu, type: \App\Enums\StockMovementType::Purchase, quantity: 40, createdBy: $admin->id, note: 'Stok awal');
        $stockService->recordMovement(outlet: $outletSudirman, ingredient: $ingGula, type: \App\Enums\StockMovementType::Purchase, quantity: 20, createdBy: $admin->id, note: 'Stok awal');
        $stockService->recordMovement(outlet: $outletSudirman, ingredient: $ingCup, type: \App\Enums\StockMovementType::Purchase, quantity: 200, createdBy: $admin->id, note: 'Stok awal');
        $stockService->recordMovement(outlet: $outletSudirman, ingredient: $ingStraw, type: \App\Enums\StockMovementType::Purchase, quantity: 200, createdBy: $admin->id, note: 'Stok awal');

        $stockService->recordMovement(outlet: $outletBandung, ingredient: $ingKopi, type: \App\Enums\StockMovementType::Purchase, quantity: 2, createdBy: $admin->id, note: 'Stok awal rendah');
        $stockService->recordMovement(outlet: $outletBandung, ingredient: $ingSusu, type: \App\Enums\StockMovementType::Purchase, quantity: 5, createdBy: $admin->id, note: 'Stok awal rendah');
        $stockService->recordMovement(outlet: $outletBandung, ingredient: $ingGula, type: \App\Enums\StockMovementType::Purchase, quantity: 3, createdBy: $admin->id, note: 'Stok awal');
        $stockService->recordMovement(outlet: $outletBandung, ingredient: $ingCup, type: \App\Enums\StockMovementType::Purchase, quantity: 30, createdBy: $admin->id, note: 'Stok awal');
        $stockService->recordMovement(outlet: $outletBandung, ingredient: $ingStraw, type: \App\Enums\StockMovementType::Purchase, quantity: 30, createdBy: $admin->id, note: 'Stok awal');

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $txS = SalesTransaction::create([
                'invoice_number' => 'INV-SUD-' . $date->format('Ymd') . '-001',
                'outlet_id' => $outletSudirman->id,
                'cashier_id' => $emp2->id,
                'transaction_date' => $date,
                'total_amount' => 128000,
                'payment_method' => 'qris',
                'status' => 'completed',
            ]);

            SalesTransactionItem::create([
                'sales_transaction_id' => $txS->id,
                'menu_item_id' => $menuEsKopi->id,
                'quantity' => 4,
                'price' => 20000,
                'subtotal' => 80000,
            ]);

            SalesTransactionItem::create([
                'sales_transaction_id' => $txS->id,
                'menu_item_id' => $menuLatte->id,
                'quantity' => 2,
                'price' => 24000,
                'subtotal' => 48000,
            ]);

            $stockService->deductForSale($txS);

            $txB = SalesTransaction::create([
                'invoice_number' => 'INV-BDG-' . $date->format('Ymd') . '-001',
                'outlet_id' => $outletBandung->id,
                'cashier_id' => $emp3->id,
                'transaction_date' => $date,
                'total_amount' => 75000,
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);

            SalesTransactionItem::create([
                'sales_transaction_id' => $txB->id,
                'menu_item_id' => $menuEsKopi->id,
                'quantity' => 3,
                'price' => 20000,
                'subtotal' => 60000,
            ]);

            SalesTransactionItem::create([
                'sales_transaction_id' => $txB->id,
                'menu_item_id' => $menuEspresso->id,
                'quantity' => 1,
                'price' => 15000,
                'subtotal' => 15000,
            ]);

            $stockService->deductForSale($txB);
        }

        $transfer1 = StockTransfer::create([
            'source' => 'transfer',
            'from_outlet_id' => $stockist->id,
            'to_outlet_id' => $outletBandung->id,
            'transferred_at' => now()->subHours(5),
            'status' => 'draft',
            'created_by' => $admin->id,
            'note' => 'Restock mingguan Outlet Bandung',
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer1->id,
            'ingredient_id' => $ingKopi->id,
            'quantity' => 10,
        ]);

        $stockService->shipTransfer($transfer1, $admin->id);

        $this->call(OpnameAndEmployeeSeeder::class);
    }
}
