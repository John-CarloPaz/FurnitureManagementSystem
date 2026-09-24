<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();  // snapshot, survives user deletion
            $table->string('event');                  // created | updated | deleted
            $table->string('method')->nullable();     // HTTP method (POST/PATCH/DELETE)
            $table->string('path')->nullable();       // request path
            $table->string('auditable_type');         // model, e.g. "Product"
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('changes')->nullable();      // {field: {old, new}} on update
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index('created_at');
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
