<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Ingredient;
use App\Models\Outlet;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class OpnameAndEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $outletSudirman = Outlet::where('name', 'like', '%Sudirman%')->first();
        $staffSudirman = User::where('email', 'staff.sudirman@sedia.com')->first();
        $emp1 = Employee::where('name', 'Budi Santoso')->first();
        $emp2 = Employee::where('name', 'Siti Aminah')->first();
        $ingKopi = Ingredient::where('name', 'like', '%Arabika%')->first();

        $stockService = app(StockService::class);

        $opname = StockOpname::create([
            'outlet_id' => $outletSudirman->id,
            'opname_date' => now()->subDay(),
            'performed_by' => $staffSudirman->id,
            'status' => 'draft',
            'note' => 'Opname bulanan Sudirman',
        ]);

        $currentKopiStock = Stock::where('outlet_id', $outletSudirman->id)->where('ingredient_id', $ingKopi->id)->value('quantity') ?? 0;

        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'ingredient_id' => $ingKopi->id,
            'system_qty' => $currentKopiStock,
            'actual_qty' => max(0, $currentKopiStock - 0.5),
        ]);

        $stockService->recordMovement(
            outlet: $outletSudirman,
            ingredient: $ingKopi,
            type: \App\Enums\StockMovementType::OpnameAdjustment,
            quantity: -0.5,
            reference: $opname,
            createdBy: $staffSudirman->id,
            note: "Opname #{$opname->id}: koreksi Biji Kopi Arabika"
        );
        $opname->update(['status' => 'applied']);

        EmployeeTransaction::create([
            'employee_id' => $emp1->id,
            'type' => 'kasbon',
            'amount' => 200000,
            'trans_date' => now()->subDays(3)->toDateString(),
            'status' => 'approved',
            'note' => 'Kasbon keperluan mendadak',
        ]);

        EmployeeTransaction::create([
            'employee_id' => $emp2->id,
            'type' => 'bonus',
            'amount' => 150000,
            'trans_date' => now()->subDays(1)->toDateString(),
            'status' => 'approved',
            'note' => 'Bonus omzet tertinggi',
        ]);
    }
}
