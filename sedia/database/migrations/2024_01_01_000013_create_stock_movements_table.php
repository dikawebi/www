<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            // sale_deduction, purchase, expired, reject, transfer_in, transfer_out, opname_adjustment
            $table->decimal('quantity', 12, 3);
            // signed: negative = stock keluar, positive = stock masuk
            $table->decimal('balance_after', 12, 3);
            $table->nullableMorphs('reference'); // reference_type, reference_id -> polymorphic to source record
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'ingredient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
