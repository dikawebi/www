<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        Schema::table('asset_sequences', function (Blueprint $table) {
            $table->dropForeign(['category_id']); // Hapus relasi lama jika ada
            $table->dropColumn('category_id');
            $table->foreignId('department_id')->constrained('departments'); // Relasi baru
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //

    }
};
