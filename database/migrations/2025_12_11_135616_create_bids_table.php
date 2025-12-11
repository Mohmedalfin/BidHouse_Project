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
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User penawar
            $table->foreignId('item_id')->constrained()->onDelete('cascade'); // Barang yang ditawar
            $table->bigInteger('amount'); // Nominal tawaran
            $table->timestamps(); // Created_at mencatat waktu bid [cite: 20]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
