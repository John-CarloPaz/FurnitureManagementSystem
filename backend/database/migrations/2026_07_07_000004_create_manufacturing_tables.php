<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cert_number')->nullable();
            $table->timestampsTz();
        });

        Schema::create('wood_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->timestampTz('received_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('production_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('strategy')->nullable();
            $table->timestampsTz();
        });

        // Production is tracked per ordered item.
        Schema::create('manufacturing_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->string('stage'); // cutting|assembly|sanding|finishing|qc
            $table->string('status')->default('pending');
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('expected_minutes')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->boolean('is_delayed')->default(false);
            $table->boolean('qc_passed')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index(['order_item_id', 'stage']);
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('production_schedules')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sequence')->nullable();
            $table->timestampTz('scheduled_start')->nullable();
            $table->timestampTz('scheduled_end')->nullable();
            $table->string('status')->default('pending');
            $table->timestampsTz();

            $table->index('order_item_id');
        });

        Schema::create('order_item_wood_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('wood_batch_id')->constrained('wood_batches')->cascadeOnDelete();
            $table->timestampsTz();

            $table->unique(['order_item_id', 'wood_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_wood_batches');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('manufacturing_stages');
        Schema::dropIfExists('production_schedules');
        Schema::dropIfExists('wood_batches');
        Schema::dropIfExists('suppliers');
    }
};
