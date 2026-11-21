<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** Admin and staff accounts, and which parts of the admin each staff member can open. */
class StaffController extends Controller
{
    public function index()
    {
        $lastSeen = DB::table('personal_access_tokens')->where('tokenable_type', User::class)
            ->selectRaw('tokenable_id, max(last_used_at) as seen')->groupBy('tokenable_id')->pluck('seen', 'tokenable_id');

        return response()->json([
            'items' => User::whereIn('role', ['admin', 'staff'])->orderByRaw("role = 'admin' desc")->orderBy('name')->get()
                ->map(fn (User $u) => $u->only(['id', 'name', 'email', 'phone', 'role', 'permissions', 'is_active', 'created_at']) + [
                    'last_seen_at' => $lastSeen[$u->id] ?? null,
                ]),
            'areas' => User::AREAS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->isStaff(), 404);
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && (($data['role'] ?? 'admin') !== 'admin' || ! ($data['is_active'] ?? true))) {
            throw ValidationException::withMessages(['role' => 'You cannot remove your own admin access.']);
        }
        if ($user->isAdmin() && (($data['role'] ?? 'admin') !== 'admin' || ! ($data['is_active'] ?? true))) {
            $this->keepOneAdmin($user);
        }

        $user->update($data);
        if (! $user->is_active) {
            $user->tokens()->delete(); // signed out everywhere
        }

        return $user;
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($user->isStaff(), 404);
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => 'You cannot delete your own account.']);
        }
        if ($user->isAdmin()) {
            $this->keepOneAdmin($user);
        }
        $user->tokens()->delete();
        $user->delete(); // orders and notes they made keep working, without their name

        return response()->noContent();
    }

    private function keepOneAdmin(User $user): void
    {
        if (User::where('role', 'admin')->where('is_active', true)->whereKeyNot($user->id)->doesntExist()) {
            throw ValidationException::withMessages(['role' => 'The shop needs at least one active admin.']);
        }
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone'         => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user?->id)],
            'role'          => ['required', 'in:admin,staff'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => [Rule::in(User::AREAS)],
            'is_active'     => ['boolean'],
            'password'      => [$user ? 'nullable' : 'required', Password::min(8)],
        ]);

        // Admins see everything; an empty list for staff means "no areas", null means "all areas"
        $data['permissions'] = $data['role'] === 'admin' ? null : array_values(array_unique($data['permissions'] ?? []));
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
