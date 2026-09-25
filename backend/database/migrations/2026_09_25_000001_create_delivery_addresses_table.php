<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();          // "Home", "Office" …
            $table->string('country')->default('Philippines');
            $table->string('province_code')->nullable();  // PSGC codes for re-populating the form
            $table->string('province_name');
            $table->string('city_code')->nullable();
            $table->string('city_name');
            $table->string('barangay_code')->nullable();
            $table->string('barangay_name');
            $table->string('street');                     // house/building no. + street
            $table->string('landmark')->nullable();
            $table->text('notes')->nullable();            // note to the driver
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_addresses');
    }
};
