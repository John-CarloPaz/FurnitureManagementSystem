<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('variation_id')->nullable()->after('product_id')
                ->constrained('product_variations')->nullOnDelete();
            $table->string('variation_name')->nullable()->after('variation_id'); // snapshot
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variation_id');
            $table->dropColumn('variation_name');
        });
    }
};
