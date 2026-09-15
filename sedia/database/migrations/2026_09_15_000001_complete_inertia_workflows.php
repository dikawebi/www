<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_active')->default(true)->index());
        Schema::table('employees', fn (Blueprint $table) => $table->unique('user_id'));
        Schema::table('employee_transactions', function (Blueprint $table) { $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('reviewed_at')->nullable(); });
        Schema::table('stock_opnames', function (Blueprint $table) { $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('applied_at')->nullable(); });
        Schema::table('stock_transfers', function (Blueprint $table) { $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('cancelled_at')->nullable(); });
        Schema::table('menu_items', fn (Blueprint $table) => $table->string('barcode')->nullable()->unique());
        Schema::table('sales_transactions', function (Blueprint $table) { $table->decimal('subtotal_amount', 14, 2)->default(0); $table->decimal('discount_amount', 14, 2)->default(0); $table->text('notes')->nullable(); });
        Schema::table('sales_transaction_items', fn (Blueprint $table) => $table->text('notes')->nullable());
    }

    public function down(): void
    {
        Schema::table('sales_transaction_items', fn (Blueprint $table) => $table->dropColumn('notes'));
        Schema::table('sales_transactions', fn (Blueprint $table) => $table->dropColumn(['subtotal_amount', 'discount_amount', 'notes']));
        Schema::table('menu_items', fn (Blueprint $table) => $table->dropUnique(['barcode']));
        Schema::table('menu_items', fn (Blueprint $table) => $table->dropColumn('barcode'));
        Schema::table('stock_transfers', fn (Blueprint $table) => $table->dropConstrainedForeignId('transferred_by'));
        Schema::table('stock_transfers', fn (Blueprint $table) => $table->dropConstrainedForeignId('cancelled_by'));
        Schema::table('stock_transfers', fn (Blueprint $table) => $table->dropColumn('cancelled_at'));
        Schema::table('stock_opnames', fn (Blueprint $table) => $table->dropConstrainedForeignId('applied_by'));
        Schema::table('stock_opnames', fn (Blueprint $table) => $table->dropColumn('applied_at'));
        Schema::table('employee_transactions', fn (Blueprint $table) => $table->dropConstrainedForeignId('reviewed_by'));
        Schema::table('employee_transactions', fn (Blueprint $table) => $table->dropColumn('reviewed_at'));
        Schema::table('employees', fn (Blueprint $table) => $table->dropUnique(['user_id']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_active'));
    }
};
