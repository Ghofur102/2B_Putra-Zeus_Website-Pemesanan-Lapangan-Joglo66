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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('fk_booking_detail_id')->nullable()->constrained('booking_details')->onDelete('cascade');
            $table->string('reference_id')->nullable()->unique()->index();
            $table->string('payment_url', 255)->nullable();
            $table->enum('payment_type', ['down payment', 'final payment', 'reschedule fee',  'refund'])->index();
            $table->string('method');
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
