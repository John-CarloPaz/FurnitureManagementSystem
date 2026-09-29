<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('tax_amount', 12, 2)->default(0)->after('delivery_fee');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('tax_amount');
            $table->foreignId('voucher_id')->nullable()->after('discount_amount')->constrained()->nullOnDelete();
            $table->string('voucher_code')->nullable()->after('voucher_id');
            $table->string('payment_method')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn(['tax_amount', 'discount_amount', 'voucher_code', 'payment_method']);
        });
    }
};
