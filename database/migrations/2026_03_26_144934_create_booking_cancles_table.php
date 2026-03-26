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
        Schema::create('booking_cancelled', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_booking_detail_id')->constrained('booking_details')->onDelete('cascade');
            $table->foreignId('fk_field_closure_id')->nullbale()->constrained('field_closures')->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->timestamp('cancle_date');
            $table->enum('status_refund', ['refundable', 'non-refundable']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_cancles');
    }
};
