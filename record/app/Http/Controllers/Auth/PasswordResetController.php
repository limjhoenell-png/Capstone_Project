<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest as PasswordResetTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('login')->with('open-reset-request', true);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'min:1', 'max:190'],
        ]);

        $identifier = trim($validated['identifier']);
        if ($identifier === '') {
            return back()->with('open-reset-request', true)
                ->withErrors(['identifier' => 'Enter your registered email, username, or employee ID.'])
                ->withInput($request->only('identifier'));
        }

        $matches = User::query()
            ->where(function ($query) use ($identifier): void {
                $normalizedIdentifier = Str::lower($identifier);
                $query->whereRaw('LOWER(email) = ?', [$normalizedIdentifier])
                    ->orWhereRaw('LOWER(name) = ?', [$normalizedIdentifier])
                    ->orWhereRaw('LOWER(full_name) = ?', [$normalizedIdentifier]);

                if (ctype_digit($identifier)) {
                    $query->orWhere('employee_id', (int) $identifier);
                }
            })
            ->limit(2)
            ->get(['id']);

        PasswordResetTicket::query()->create([
            'user_id' => $matches->count() === 1 ? $matches->first()->id : null,
            'identifier' => $identifier,
        ]);

        return redirect()->route('login')->with(
            'status',
            'If the account can be identified, an administrator will verify the request locally.'
        );
    }
}
