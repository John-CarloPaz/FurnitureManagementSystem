<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('expected_delivery_at')->nullable()->after('delivered_at'); // promised by, from lead time
            $table->boolean('is_delayed')->default(false)->after('expected_delivery_at');
            $table->timestamp('delay_notified_at')->nullable()->after('is_delayed');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['expected_delivery_at', 'is_delayed', 'delay_notified_at']);
        });
    }
};
