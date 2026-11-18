<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('logbook_entries')) {
            return;
        }

        Schema::create('logbook_entries', function (Blueprint $table): void {
            $table->id();
            $table->integer('user_id')->index();
            $table->dateTime('entry_at')->index();
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logbook_entries');
    }
};
