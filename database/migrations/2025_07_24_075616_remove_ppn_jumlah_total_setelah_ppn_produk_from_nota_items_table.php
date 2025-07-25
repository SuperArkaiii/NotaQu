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
       Schema::table('nota_items', function (Blueprint $table) {
    if (Schema::hasColumn('nota_items', 'ppn')) {
        $table->dropColumn('ppn');
    }

    if (Schema::hasColumn('nota_items', 'total_setelah_ppn_produk')) {
        $table->dropColumn('total_setelah_ppn_produk');
    }
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nota_items', function (Blueprint $table) {

        });
    }
};
