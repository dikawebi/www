<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Outlet;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAndOutletScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_accessible_outlet_id()
    {
        $outlet = Outlet::create(['name' => 'Outlet A']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'outlet_id' => $outlet->id,
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'outlet_id' => $outlet->id,
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertNull($admin->accessibleOutletId());

        $this->assertFalse($staff->isAdmin());
        $this->assertEquals($outlet->id, $staff->accessibleOutletId());
    }

    public function test_two_stage_stock_transfer_ship_and_receive()
    {
        $outletA = Outlet::create(['name' => 'Outlet A']);
        $outletB = Outlet::create(['name' => 'Outlet B']);
        $ingredient = Ingredient::create(['name' => 'Kopi Biji', 'unit' => 'kg']);

        // Stock awal Outlet A: 10 kg
        $stockA = Stock::create([
            'outlet_id' => $outletA->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 10,
        ]);

        $transfer = StockTransfer::create([
            'source' => 'transfer',
            'from_outlet_id' => $outletA->id,
            'to_outlet_id' => $outletB->id,
            'status' => 'draft',
            'transferred_at' => now(),
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 4,
        ]);

        $stockService = app(StockService::class);

        // 1. Ship transfer
        $stockService->shipTransfer($transfer);
        $transfer->refresh();

        $this->assertEquals('sent', $transfer->status);
        $stockA->refresh();
        $this->assertEquals(6, (float) $stockA->quantity); // 10 - 4 = 6

        // Stock Outlet B belum bertambah sebelum received
        $stockB = Stock::where('outlet_id', $outletB->id)
            ->where('ingredient_id', $ingredient->id)
            ->first();
        $this->assertNull($stockB);

        // 2. Receive transfer
        $stockService->receiveTransfer($transfer);
        $transfer->refresh();

        $this->assertEquals('received', $transfer->status);
        $stockB = Stock::where('outlet_id', $outletB->id)
            ->where('ingredient_id', $ingredient->id)
            ->first();
        $this->assertNotNull($stockB);
        $this->assertEquals(4, (float) $stockB->quantity);
    }
}
