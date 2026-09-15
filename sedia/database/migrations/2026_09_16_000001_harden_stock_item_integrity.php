<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('stock_transfer_items')->select('stock_transfer_id', 'ingredient_id')->selectRaw('MIN(id) as keep_id, SUM(quantity) as total_quantity')->groupBy('stock_transfer_id', 'ingredient_id')->havingRaw('COUNT(*) > 1')->get()->each(function ($duplicate) {
            DB::table('stock_transfer_items')->where('id', $duplicate->keep_id)->update(['quantity' => $duplicate->total_quantity]);
            DB::table('stock_transfer_items')->where('stock_transfer_id', $duplicate->stock_transfer_id)->where('ingredient_id', $duplicate->ingredient_id)->where('id', '!=', $duplicate->keep_id)->delete();
        });
        DB::table('stock_opname_items')->select('stock_opname_id', 'ingredient_id')->selectRaw('MIN(id) as keep_id')->groupBy('stock_opname_id', 'ingredient_id')->havingRaw('COUNT(*) > 1')->get()->each(function ($duplicate) {
            DB::table('stock_opname_items')->where('stock_opname_id', $duplicate->stock_opname_id)->where('ingredient_id', $duplicate->ingredient_id)->where('id', '!=', $duplicate->keep_id)->delete();
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->unique(['stock_transfer_id', 'ingredient_id'], 'transfer_ingredient_unique');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->unique(['stock_opname_id', 'ingredient_id'], 'opname_ingredient_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropUnique('transfer_ingredient_unique');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropUnique('opname_ingredient_unique');
        });
    }
};
