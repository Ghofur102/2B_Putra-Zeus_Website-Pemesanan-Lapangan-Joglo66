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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 60);
            $table->string('phone_number', 15)->nullable();
            $table->text('address')->nullable();
            $table->string('position', 50);
            $table->integer('base_salary')->unsigned(); 
            $table->date('join_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
