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
        Schema::create('booking_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('fk_attribute_id')->constrained('attributes');
            $table->timestamp('start_time');
            $table->timestamp('return_time');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('price');
            $table->unsignedInteger('total');
            $table->text('reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_attributes');
    }
};
