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
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_field_id')->constrained('fields')->onDelete('cascade');
            $table->string('name', 100);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('price_hour');
            $table->string('type', 100)->default('lainnya');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('price_hour');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
