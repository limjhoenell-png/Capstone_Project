<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminPassword = env('ADMIN_SEED_PASSWORD', 'ChangeMe!2026');
        $employeePassword = env('EMPLOYEE_SEED_PASSWORD', 'ChangeMe!2026');

        $adminData = [
            'name' => 'System Admin',
            'password' => $adminPassword,
            'role' => User::ROLE_ADMIN,
            'position' => 'Administrator',
        ];

        if (Schema::hasColumn('users', 'full_name')) {
            $adminData['full_name'] = 'System Admin';
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            $adminData
        );

        $employeeData = [
            'name' => 'Employee User',
            'password' => $employeePassword,
            'role' => User::ROLE_EMPLOYEE,
            'position' => 'Staff',
        ];

        if (Schema::hasColumn('users', 'full_name')) {
            $employeeData['full_name'] = 'Employee User';
        }

        User::query()->updateOrCreate(
            ['email' => 'employee@example.com'],
            $employeeData
        );
    }
}
