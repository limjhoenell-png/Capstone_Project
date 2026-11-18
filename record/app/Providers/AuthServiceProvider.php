<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Policies\AttendancePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Attendance::class, AttendancePolicy::class);
    }
}
