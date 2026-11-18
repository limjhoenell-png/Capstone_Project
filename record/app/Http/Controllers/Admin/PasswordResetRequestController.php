<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest as PasswordResetTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetRequestController extends Controller
{
    public function index(): View
    {
        return view('admin.password-reset-requests.index', [
            'requests' => PasswordResetTicket::query()
                ->with(['user:id,name,full_name,email,employee_id,role'])
                ->where('status', 'pending')
                ->latest()
                ->paginate(5),
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'full_name', 'email', 'employee_id', 'role']),
        ]);
    }

    public function resolve(Request $request, PasswordResetTicket $passwordResetRequest): RedirectResponse
    {
        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'identity_verified' => ['accepted'],
        ]);

        DB::transaction(function () use ($request, $passwordResetRequest, $validated): void {
            $ticket = PasswordResetTicket::query()
                ->whereKey($passwordResetRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($ticket->status === 'pending', 409, 'This password reset request has already been handled.');

            $user = User::query()
                ->whereKey($validated['target_user_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();

            $ticket->forceFill([
                'user_id' => $user->id,
                'status' => 'resolved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();
        });

        return redirect()->route('admin.password-reset-requests.index')
            ->with('status', 'Password updated. Provide the new credential to the verified account holder through a trusted local channel.');
    }

    public function dismiss(Request $request, PasswordResetTicket $passwordResetRequest): RedirectResponse
    {
        DB::transaction(function () use ($request, $passwordResetRequest): void {
            $ticket = PasswordResetTicket::query()
                ->whereKey($passwordResetRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($ticket->status === 'pending', 409, 'This password reset request has already been handled.');

            $ticket->forceFill([
                'status' => 'dismissed',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();
        });

        return redirect()->route('admin.password-reset-requests.index')
            ->with('status', 'Password reset request dismissed.');
    }
}
