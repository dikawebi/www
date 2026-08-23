<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('pay_date'); // Tanggal Gajian
            $table->date('period_start'); // Periode Mulai
            $table->date('period_end'); // Periode Akhir
            $table->decimal('base_salary', 12, 2)->default(0); // Gaji Pokok
            $table->decimal('bonus_masuk', 12, 2)->default(0); // Bonus Masuk
            $table->decimal('bonus_goreng', 12, 2)->default(0); // Bonus Goreng
            $table->decimal('kasbon_deduction', 12, 2)->default(0); // Kasbon (potongan)
            $table->decimal('total_salary', 12, 2)->default(0); // Total Gaji (dihitung otomatis)
            $table->string('status')->default('draft'); // draft, paid, cancelled
            $table->text('note')->nullable(); // Catatan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
