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
        $table->decimal('subtotal', 15, 2)->after('nomor_po')->nullable();
        $table->decimal('dpp', 15, 2)->after('subtotal')->nullable();
        $table->decimal('ppn', 15, 2)->after('dpp')->nullable();
        $table->decimal('total', 15, 2)->after('ppn')->nullable();
    });
}

public function down(): void
{
    Schema::table('nota_penjualans', function (Blueprint $table) {
        $table->dropColumn(['subtotal', 'dpp', 'ppn', 'total']);
    });
}

};
