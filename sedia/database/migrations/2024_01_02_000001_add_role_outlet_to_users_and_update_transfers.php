<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // role: admin = akses penuh, staff = hanya outlet sendiri
            $table->string('role')->default('staff')->after('email');
            $table->foreignId('outlet_id')->nullable()->after('role')
                ->constrained('outlets')->nullOnDelete();
        });

        // Ubah flow status transfer: draft → sent → received (completed) | cancelled
        // 'sent'     = outlet asal sudah kirim, stok outlet asal dipotong
        // 'received' = outlet tujuan konfirmasi terima, stok outlet tujuan ditambah
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
            // Catat siapa yang menerima
            $table->foreignId('received_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('received_at')->nullable()->after('transferred_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['outlet_id']);
            $table->dropColumn(['role', 'outlet_id']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['received_by']);
            $table->dropColumn(['received_by', 'received_at']);
            $table->string('status')->default('completed')->change();
        });
    }
};
