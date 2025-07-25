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
        $table->decimal('jumlah', 15, 2)->after('pajak')->nullable();
    });
}

public function down(): void
{
    Schema::table('nota_items', function (Blueprint $table) {
        $table->dropColumn('jumlah');
    });
}

};
