<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->index(['is_active', 'category']);
            $table->index('name');
        });

        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->index(['outlet_id', 'transaction_date']);
            $table->index(['status', 'transaction_date']);
            $table->index('outlet_id');
        });

        Schema::table('sales_transaction_items', function (Blueprint $table) {
            $table->index('menu_item_id');
            $table->index('sales_transaction_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['outlet_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropIndex(['menu_items_is_active_category_index']);
            $table->dropIndex(['menu_items_name_index']);
        });

        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropIndex(['sales_transactions_outlet_id_transaction_date_index']);
            $table->dropIndex(['sales_transactions_status_transaction_date_index']);
            $table->dropIndex(['sales_transactions_outlet_id_index']);
        });

        Schema::table('sales_transaction_items', function (Blueprint $table) {
            $table->dropIndex(['sales_transaction_items_menu_item_id_index']);
            $table->dropIndex(['sales_transaction_items_sales_transaction_id_index']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['stock_movements_outlet_id_ingredient_id_index']);
        });
    }
};
