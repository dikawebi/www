<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            // Harga beli per unit (per satuan yang sama dengan kolom `unit`),
            // dipakai untuk hitung Harga Pokok Penjualan (HPP) & margin menu.
            $table->decimal('cost_per_unit', 12, 2)->default(0)->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('cost_per_unit');
        });
    }
};
