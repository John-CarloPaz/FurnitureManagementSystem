<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('material')->nullable();
            $table->string('wood_type')->nullable();
            $table->string('finish')->nullable();
            // Structured dimensions (cm) + weight (kg).
            $table->decimal('width_cm', 8, 1)->nullable();
            $table->decimal('depth_cm', 8, 1)->nullable();
            $table->decimal('height_cm', 8, 1)->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->string('status')->default('DRAFT')->index(); // DRAFT|PUBLISHED|ARCHIVED
            $table->timestampTz('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestampsTz();
        });

        // One 3D model per PRODUCT (versioned). The company owns the design.
        Schema::create('models_3d', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestampsTz();

            $table->unique('product_id');
        });

        Schema::create('model_3d_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('models_3d')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('file_path');
            $table->string('format'); // glb | obj
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('change_log')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['model_id', 'version']);
        });

        Schema::table('models_3d', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('model_3d_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('models_3d', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        Schema::dropIfExists('model_3d_versions');
        Schema::dropIfExists('models_3d');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
