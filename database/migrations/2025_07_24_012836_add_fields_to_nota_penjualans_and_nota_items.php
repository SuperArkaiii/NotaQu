<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nota_penjualans', function (Blueprint $table) {
            $table->string('satuan')->nullable(); // opsional, jika dari Penjualan
            $table->text('keterangan')->nullable(); // keterangan umum (faktur/barang)
            $table->decimal('total_setelah_ppn', 15, 2)->nullable();
        });

        Schema::table('nota_items', function (Blueprint $table) {
            $table->string('satuan')->nullable();
            $table->text('keterangan_produk')->nullable();
            $table->decimal('pajak', 15, 2)->nullable(); // bisa persentase atau nilai
        });
    }

    public function down(): void
    {
        Schema::table('nota_penjualans', function (Blueprint $table) {
            // Hapus hanya kolom yang masih ada di atas
            $table->dropColumn(['satuan', 'keterangan', 'total_setelah_ppn']);
        });

        Schema::table('nota_items', function (Blueprint $table) {
            $table->dropColumn(['satuan', 'keterangan_produk', 'pajak']);
        });
    }
};
