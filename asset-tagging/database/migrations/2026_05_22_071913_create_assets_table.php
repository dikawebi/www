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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_id')->unique();
            $table->string('name');

            // Relasi ke tabel master
            $table->foreignId('category_id')->constrained()->onDelete('restrict');
            $table->foreignId('location_id')->constrained()->onDelete('restrict');
            $table->foreignId('department_id')->constrained()->onDelete('restrict');

            $table->string('pr_number')->nullable();
            $table->string('po_number')->nullable();
            $table->string('user_name')->nullable();
            $table->enum('status', ['In use', 'Idle', 'Broke'])->default('Idle');
            $table->json('images')->nullable(); // Menampung 4 field gambar sekaligus
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
