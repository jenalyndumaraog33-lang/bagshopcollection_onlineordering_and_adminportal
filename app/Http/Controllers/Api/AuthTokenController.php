<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTokenController extends Controller
{
    public function store(Request $request, ActivityLogger $activityLogger): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:100'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'The provided credentials are invalid.'], 401);
        }

        $issuedToken = $user->createToken(
            $validated['name'] ?? 'API token',
            ['*'],
            now()->addDays($validated['expires_in_days'] ?? 30),
        );
        $activityLogger->record($user, 'api_token.created', null, 'Issued an API access token.');

        return response()->json($this->tokenResponse($user, $issuedToken), 201);
    }

    public function register(Request $request, ActivityLogger $activityLogger): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
        $issuedToken = $user->createToken('API registration token', ['*'], now()->addDays(30));
        $activityLogger->record($user, 'user.registered', $user, 'Registered through the API.');

        return response()->json($this->tokenResponse($user, $issuedToken), 201);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => $validated['email']]);

        return response()->json([
            'message' => 'If an account exists for that email, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'The password reset link is invalid or expired.'], 422);
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => strtolower($user->role),
        ]);
    }

    public function destroy(Request $request, ActivityLogger $activityLogger): JsonResponse
    {
        $actor = $request->user();
        $token = $actor?->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken, 401);
        $token->delete();
        $activityLogger->record($actor, 'api_token.revoked', null, 'Revoked an API access token.');

        return response()->json(['message' => 'Token revoked.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenResponse(User $user, NewAccessToken $issuedToken): array
    {
        return [
            'token' => $issuedToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $issuedToken->accessToken->expires_at?->toISOString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => strtolower($user->role),
            ],
        ];
    }
}
