<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $users = User::query()->orderBy('name')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(User::roles())],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);
        $activityLogger->record($request->user(), 'user.created', $user, "Created user {$user->email}.");

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(User::roles())],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->id === $request->user()->id && (! $request->boolean('is_active') || $validated['role'] !== User::ROLE_ADMIN)) {
            return redirect()->route('admin.users.edit', $user)->with('error', 'You cannot deactivate or demote your own account.');
        }

        if ($user->isAdmin() && (! $request->boolean('is_active') || $validated['role'] !== User::ROLE_ADMIN)
            && User::query()->whereRaw('LOWER(role) = ?', [User::ROLE_ADMIN])->where('is_active', true)->count() <= 1) {
            return redirect()->route('admin.users.edit', $user)->with('error', 'At least one active administrator must remain.');
        }

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        $activityLogger->record($request->user(), 'user.updated', $user, "Updated user {$user->email}.");

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user, ActivityLogger $activityLogger): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        if ($user->isAdmin() && $user->is_active
            && User::query()->whereRaw('LOWER(role) = ?', [User::ROLE_ADMIN])->where('is_active', true)->count() <= 1) {
            return redirect()->route('admin.users.index')->with('error', 'At least one active administrator must remain.');
        }

        $activityLogger->record(request()->user(), 'user.deleted', $user, "Deleted user {$user->email}.");
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
