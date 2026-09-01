<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->unique()->constrained()->onDelete('cascade');
            $table->string('prefix');
            $table->string('format')->default('{prefix}-{year}-{sequence}');
            $table->integer('next_value')->default(1);
            $table->integer('padding')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_sequences');
    }
};
