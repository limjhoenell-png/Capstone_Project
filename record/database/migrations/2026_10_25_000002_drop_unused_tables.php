<?php

use Illuminate\Database\Migrations\Migration;
return new class extends Migration
{
    public function up(): void
    {
    }

    public function down(): void
    {
        // These tables are unused with file sessions, file cache, and a sync queue.
    }
};
