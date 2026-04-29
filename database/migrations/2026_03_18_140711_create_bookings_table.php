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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('fk_field_id')->constrained('fields')->onDelete('cascade');
            $table->string('team_name', 50);
            $table->string('customer_phone', 50)->default('-');
            $table->string('customer_email', 50)->default('-');
            $table->string('notes', 50)->default('-');
            $table->date('booking_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
