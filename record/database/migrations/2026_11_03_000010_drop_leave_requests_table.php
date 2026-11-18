<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('leave_requests');
    }

    public function down(): void
    {
        // Intentionally irreversible: the decommissioned table must not be recreated.
    }
};
