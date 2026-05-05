<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // This migration is disabled - see 2026_03_18_155142_create_payments_table.php instead
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
