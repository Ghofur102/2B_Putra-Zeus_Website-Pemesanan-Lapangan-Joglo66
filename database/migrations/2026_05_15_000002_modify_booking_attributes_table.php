<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_attributes', function (Blueprint $table) {
            $table->foreignId('fk_booking_id')->nullable()->change();
            $table->date('transaction_date')->nullable()->after('fk_attribute_id');
            $table->enum('status', ['dipinjam', 'dikembalikan', 'terlambat'])->default('dipinjam')->after('total');
            $table->string('customer_name', 100)->nullable()->after('status');
            $table->string('customer_phone', 20)->nullable()->after('customer_name');
            $table->unsignedInteger('duration_hours')->default(1)->after('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('booking_attributes', function (Blueprint $table) {
            $table->foreignId('fk_booking_id')->nullable(false)->change();
            $table->dropColumn(['transaction_date', 'status', 'customer_name', 'customer_phone', 'duration_hours']);
        });
    }
};
