<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('PLACED')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('downpayment', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('payment_status')->default('UNPAID'); // UNPAID|PARTIAL|PAID
            $table->text('delivery_address')->nullable(); // encrypted
            $table->text('notes')->nullable();
            $table->timestampTz('placed_at')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampsTz();

            $table->index('customer_id');
            $table->index('created_at');
        });

        Schema::create('orders_archive', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('order_number');
            $table->unsignedBigInteger('customer_id');
            $table->string('status');
            $table->decimal('total', 12, 2)->default(0);
            $table->timestampTz('created_at')->nullable();
            $table->timestampTz('updated_at')->nullable();
            $table->timestampTz('archived_at')->useCurrent();
        });

        Schema::create('order_state_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestampsTz();

            $table->index('order_id');
        });

        // Payment log (who recorded it -> BIR/DTI audit, caps §2.4).
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_state_transitions');
        Schema::dropIfExists('orders_archive');
        Schema::dropIfExists('orders');
    }
};
