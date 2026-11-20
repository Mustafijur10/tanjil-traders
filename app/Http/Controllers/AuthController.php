<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Login with email or phone. `admin: true` only lets admin and staff accounts in. */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'admin'    => ['sometimes', 'boolean'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $data['login'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'These details do not match our records.']);
        }
        if (! $user->is_active) {
            throw ValidationException::withMessages(['login' => 'This account has been disabled.']);
        }
        if (! empty($data['admin']) && ! $user->isStaff()) {
            throw ValidationException::withMessages(['login' => 'This account cannot open the admin panel.']);
        }

        return response()->json([
            'user'  => $user,
            'token' => $user->createToken($user->isStaff() ? 'admin' : 'store')->plainTextToken,
        ]);
    }

    /** Storefront sign-up. Always creates a customer. */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'phone'    => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email'    => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $user = User::create($data + ['role' => 'customer']);

        return response()->json([
            'user'  => $user,
            'token' => $user->createToken('store')->plainTextToken,
        ], 201);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /** Theme, language and header options follow the user to every device. */
    public function updatePreferences(Request $request)
    {
        $data = $request->validate(['preferences' => ['required', 'array']]);
        $user = $request->user();
        $user->preferences = array_merge($user->preferences ?? [], $data['preferences']);
        $user->save();

        return $user;
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'            => ['nullable', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'current_password' => ['required_with:password', 'current_password:sanctum'],
            'password'         => ['nullable', 'confirmed', PasswordRule::min(8)],
        ]);

        unset($data['current_password']);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return $user;
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        // Same answer whether or not the email exists, so accounts cannot be discovered.
        return response()->json(['message' => 'If this email has an account, a reset link has been sent.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['message' => 'Your password has been reset.']);
    }
}
