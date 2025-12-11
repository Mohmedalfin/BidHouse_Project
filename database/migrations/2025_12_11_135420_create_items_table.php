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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // ID Admin pembuat
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('image')->nullable(); // Opsional sesuai soal [cite: 16]
            $table->bigInteger('initial_price'); // Harga awal
            $table->bigInteger('current_price')->default(0); // Harga saat ini (biar query cepat)
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->dateTime('end_at'); // Batas waktu lelang [cite: 20]
            $table->foreignId('winner_id')->nullable()->constrained('users')->onDelete('set null'); // Pemenang (diisi saat tutup)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
