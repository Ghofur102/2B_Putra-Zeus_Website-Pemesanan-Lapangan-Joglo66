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
        Schema::create('booking_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('fk_field_id')->constrained('fields')->onDelete('cascade');
            $table->time('start_play_time');
            $table->time('end_play_time');
            $table->date('play_date');
            $table->unsignedBigInteger('price');
            $table->enum('status', ['active', 'waiting', 'finish', 'cancelled from admin', 'reschedule from admin', 'cancelled from tenant', 'reschedule from tenant', "field close"]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_details');
    }
};
