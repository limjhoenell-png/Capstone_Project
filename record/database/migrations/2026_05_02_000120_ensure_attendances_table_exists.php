<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some environments have the attendances migration marked "run" but the table
     * was never created or was dropped. Create the table if it is missing.
     */
    public function up(): void
    {
        if (Schema::hasTable('attendances')) {
            return;
        }

        Schema::create('attendances', function (Blueprint $table): void {
            $table->id();
            $table->integer('user_id')->index();
            $table->date('attendance_date')->index();
            $table->dateTime('time_in')->nullable();
            $table->dateTime('time_out')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Intentionally empty: do not drop; table may pre-exist or hold data.
    }
};
