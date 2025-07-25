<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nota_penjualans', function (Blueprint $table) {
            $table->foreignId('ongkos_packing_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('nota_penjualans', function (Blueprint $table) {
            $table->dropForeign(['ongkos_packing_id']);
            $table->dropColumn('ongkos_packing_id');
        });
    }
};
