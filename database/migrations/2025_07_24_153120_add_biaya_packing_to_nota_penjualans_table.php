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
        $table->bigInteger('biaya_packing')->default(0);
    });
}

public function down(): void
{
    Schema::table('nota_penjualans', function (Blueprint $table) {
        $table->dropColumn('biaya_packing');
    });
}

};
