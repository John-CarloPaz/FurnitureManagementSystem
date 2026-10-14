<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('color_hex');  // the variation's own photo
            $table->string('model_path')->nullable()->after('image_path'); // its own 3D model file
            $table->string('model_format')->nullable()->after('model_path');
        });

        Schema::table('model_generations', function (Blueprint $table) {
            // When set, the generated GLB belongs to this variation instead of the product's base model.
            $table->foreignId('variation_id')->nullable()->after('product_id')
                ->constrained('product_variations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('model_generations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variation_id');
        });
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'model_path', 'model_format']);
        });
    }
};
