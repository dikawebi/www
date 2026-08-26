<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->json('payments')->nullable()->after('payment_method');
            $table->decimal('paid_amount', 14, 2)->nullable()->after('payments');
            $table->decimal('change_amount', 14, 2)->default(0)->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn(['payments', 'paid_amount', 'change_amount']);
        });
    }
};
