<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nota_items', function (Blueprint $table) {
            $table->decimal('ppn', 12, 2)->nullable()->after('pajak');
        });
    }

    public function down(): void
    {
        Schema::table('nota_items', function (Blueprint $table) {
            $table->dropColumn('ppn');
        });
    }
};
