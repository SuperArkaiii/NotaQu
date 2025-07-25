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
        Schema::table('nota_penjualans', function (Blueprint $table) {
        $table->dropColumn('total_setelah_ppn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nota_penjualans', function (Blueprint $table) {
        $table->double('total_setelah_ppn')->default(0); // atau nullable sesuai sebelumnya
        });
    }
};
