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
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('fk_expense_id')->constrained('expenses')->cascadeOnDelete();
            $table->integer('amount_paid')->unsigned();
            $table->enum('period_month', ['january','february','march','april','may','june','july','august','september','october','november','december']);
            $table->year('period_year');
            $table->date('payment_date');
            $table->integer('bonus')->unsigned()->default(0);
            $table->integer('deduction')->unsigned()->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
