<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nota_penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_faktur')->nullable();
            $table->date('tanggal');
            $table->date('jatuh_tempo');
            $table->string('biaya_kirim')->nullable();
            $table->date('tanggal_kirim')->nullable();
            $table->string('nomor_po')->nullable();
            $table->foreignId('data_pelanggan_id')->nullable()->constrained('data_pelanggans')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_penjualans');
    }
};
