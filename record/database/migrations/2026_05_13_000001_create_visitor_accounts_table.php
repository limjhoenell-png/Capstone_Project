<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name', 150);
            $table->string('address', 255);
            $table->string('contact_number', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_accounts');
    }
};
