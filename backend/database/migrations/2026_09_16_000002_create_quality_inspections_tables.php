<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('passed');
            $table->text('reason')->nullable();      // why it failed (required on a fail)
            $table->unsignedInteger('attempt')->default(1);
            $table->timestamps();

            $table->index(['order_item_id', 'attempt']);
        });

        Schema::create('quality_inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_inspection_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_inspection_photos');
        Schema::dropIfExists('quality_inspections');
    }
};
