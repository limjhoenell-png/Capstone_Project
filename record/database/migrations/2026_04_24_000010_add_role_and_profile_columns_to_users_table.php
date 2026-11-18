<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable();
            }

            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'password')) {
                $table->string('password')->nullable();
            }

            if (! Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }

            if (! Schema::hasColumn('users', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default(User::ROLE_EMPLOYEE)->index();
            }

            if (! Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable();
            }

            if (! Schema::hasColumn('users', 'contact_number')) {
                $table->string('contact_number')->nullable();
            }
        });

        if (Schema::hasColumn('users', 'email')) {
            if (Schema::hasColumn('users', 'name') && Schema::hasColumn('users', 'full_name')) {
                \Illuminate\Support\Facades\DB::table('users')
                    ->whereNull('name')
                    ->update(['name' => \Illuminate\Support\Facades\DB::raw('full_name')]);
            }

            $existingAdmin = \Illuminate\Support\Facades\DB::table('users')
                ->where('name', 'System Admin')
                ->orWhere('role', User::ROLE_ADMIN)
                ->first();

            if ($existingAdmin !== null && empty($existingAdmin->email)) {
                \Illuminate\Support\Facades\DB::table('users')
                    ->where('id', $existingAdmin->id)
                    ->update(['email' => 'admin@gmail.com']);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'contact_number')) {
                $table->dropColumn('contact_number');
            }
            if (Schema::hasColumn('users', 'position')) {
                $table->dropColumn('position');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
