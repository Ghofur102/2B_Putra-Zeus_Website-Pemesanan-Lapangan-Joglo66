<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_field_id')->constrained('fields')->onDelete('cascade');
            $table->foreignId('fk_user_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('category', 50);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->date('expense_date');
            $table->string('proof_photo', 255)->nullable();
            $table->text('note')->nullable();
            $table->date('generate_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
