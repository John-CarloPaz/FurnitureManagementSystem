<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');                 // the uploaded photo the model is generated from
            $table->string('provider');                   // e.g. meshy
            $table->string('provider_task_id')->nullable();
            $table->string('status')->default('pending'); // pending | processing | succeeded | failed
            $table->unsignedTinyInteger('progress')->default(0);
            $table->text('error')->nullable();
            $table->foreignId('model_version_id')->nullable()->constrained('model_3d_versions')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_generations');
    }
};
