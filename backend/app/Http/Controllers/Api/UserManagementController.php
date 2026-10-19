<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class UserManagementController extends Controller
{
    public function updateOwnCredentials(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->ensureCredentialsProvided($validated);
        $this->ensureCurrentPassword($validated['current_password'], $user);

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $user->email = $validated['email'];
        }

        if (array_key_exists('password', $validated)) {
            $user->password = $validated['password'];
            $currentToken = $request->user()->currentAccessToken();
            if ($currentToken instanceof PersonalAccessToken) {
                $user->tokens()->where('id', '!=', $currentToken->getKey())->delete();
            }
        }

        $user->save();

        return response()->json([
            'message' => 'Kredensial akun berhasil diperbarui.',
            'user' => $user,
        ]);
    }

    public function cashiers()
    {
        return User::query()
            ->where('role', 'kasir')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status']);
    }

    public function updateCashierCredentials(Request $request, User $cashier)
    {
        abort_unless($cashier->role === 'kasir', 404);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($cashier->id)],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->ensureCredentialsProvided($validated);
        $this->ensureCurrentPassword($validated['current_password'], $request->user());

        if (array_key_exists('name', $validated)) {
            $cashier->name = $validated['name'];
        }

        if (array_key_exists('email', $validated)) {
            $cashier->email = $validated['email'];
        }

        if (array_key_exists('password', $validated)) {
            $cashier->password = $validated['password'];
            $cashier->tokens()->delete();
        }

        $cashier->save();

        return response()->json([
            'message' => 'Kredensial kasir berhasil diperbarui.',
            'cashier' => $cashier,
        ]);
    }

    private function ensureCredentialsProvided(array $validated): void
    {
        if (!array_key_exists('name', $validated)
            && !array_key_exists('email', $validated)
            && !array_key_exists('password', $validated)) {
            throw ValidationException::withMessages([
                'name' => ['Masukkan nama, email, atau password baru.'],
            ]);
        }
    }

    private function ensureCurrentPassword(string $password, User $user): void
    {
        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }
    }
}
