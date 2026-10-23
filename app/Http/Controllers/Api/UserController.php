<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'is_active', 'created_at'])
            ->orderBy('name')
            ->paginate(30);

        return response()->json($users);
    }

    public function update(Request $request, User $user, ActivityLogger $activityLogger): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(User::roles())],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->id === $request->user()->id && (! $request->boolean('is_active') || $validated['role'] !== User::ROLE_ADMIN)) {
            return response()->json(['message' => 'You cannot deactivate or demote your own account.'], 422);
        }

        if ($user->isAdmin() && (! $request->boolean('is_active') || $validated['role'] !== User::ROLE_ADMIN)
            && User::query()->whereRaw('LOWER(role) = ?', [User::ROLE_ADMIN])->where('is_active', true)->count() <= 1) {
            return response()->json(['message' => 'At least one active administrator must remain.'], 422);
        }

        $user->update($validated);

        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        $activityLogger->record($request->user(), 'user.updated', $user, "Updated user {$user->email} via API.");

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => strtolower($user->role),
            'is_active' => $user->is_active,
        ]);
    }
}
