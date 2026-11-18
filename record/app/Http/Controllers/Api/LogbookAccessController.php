<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LogbookAccessController extends Controller
{
    private const DENIED_MESSAGE = 'Access Denied: Employee is not authorized to access Logbook module.';

    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email'],
            'password' => ['nullable', 'string'],
            'fingerprint_id' => ['nullable', 'string', 'max:64'],
            'employee_id' => ['nullable', 'string', 'max:64'],
        ]);

        $user = null;

        if (! empty($validated['email']) && array_key_exists('password', $validated)) {
            $candidate = User::query()->where('email', $validated['email'])->first();
            if (! $candidate || ! Hash::check((string) $validated['password'], $candidate->password)) {
                return response()->json([
                    'authorized' => false,
                    'valid_identity' => false,
                    'message' => 'Invalid email or password.',
                ], 401);
            }
            $user = $candidate;
        } else {
            $identifier = trim((string) ($validated['fingerprint_id'] ?? $validated['employee_id'] ?? ''));
            if ($identifier !== '') {
                $user = User::query()->where('employee_id', $identifier)->first();
            }
        }

        if (! $user instanceof User) {
            return response()->json([
                'authorized' => false,
                'valid_identity' => false,
                'message' => 'Invalid identity.',
            ], 404);
        }

        if (! $user->canAccessLogbook()) {
            return response()->json([
                'authorized' => false,
                'valid_identity' => true,
                'message' => self::DENIED_MESSAGE,
            ], 403);
        }

        return response()->json([
            'authorized' => true,
            'valid_identity' => true,
            'message' => 'Logbook access authorized.',
            'user_id' => $user->id,
            'role' => $user->role,
        ]);
    }
}
