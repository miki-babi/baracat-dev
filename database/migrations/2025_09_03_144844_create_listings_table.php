<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('location')->nullable();
            $table->string('type'); // car, property
            $table->string('status')->default('available'); // available, sold, rented
            $table->year('year')->nullable(); // model year or construction year
            $table->string('condition')->nullable(); // new, used, renovated
            $table->string('size')->nullable(); // "120 sqm" or "1800 cc"
            $table->string('capacity')->nullable(); // "3 bedrooms" or "5 seats"
            $table->json('features')->nullable(); // tags/amenities
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
