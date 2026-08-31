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
            if (Schema::hasColumn('asset_sequences', 'category_id')) {
                // Drop foreign key if exists - handle both naming conventions
                try { $table->dropForeign(['category_id']); } catch (\Throwable $e) {}
                $table->dropColumn('category_id');
            }
        });
        Schema::table('asset_sequences', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_sequences', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained('departments'); // Relasi baru, nullable to avoid NOT NULL violation on existing rows
            }
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
