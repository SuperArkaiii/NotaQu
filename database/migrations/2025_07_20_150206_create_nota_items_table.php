<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nota_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_penjualan_id')->constrained('nota_penjualans')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->integer('harga');
            $table->integer('diskon')->default(0);
            $table->integer('jumlah');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_items');
    }
};
