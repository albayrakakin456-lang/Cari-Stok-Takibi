<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /** E-posta ve şifreyi doğrulayıp kullanıcıya ait Bearer token üretir. */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'E-posta veya şifre hatalı.',
            ], 401);
        }

        // Aynı cihaz adıyla yeniden giriş yapılırsa eski tokenı yenisiyle değiştiririz.
        $user->tokens()->where('name', $validated['device_name'])->delete();

        $token = $user->createToken(
            $validated['device_name'],
            ['api:read', 'api:write'],
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Giriş başarılı.',
            'data' => [
                'user' => $user->only(['id', 'name', 'email']),
                'token_type' => 'Bearer',
                'token' => $token,
                'expires_in_minutes' => config('sanctum.expiration'),
            ],
        ]);
    }

    /** Kullanılan tokenın bağlı olduğu kullanıcıyı döndürür. */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->only(['id', 'name', 'email']),
        ]);
    }

    /** Yalnızca bu istekte kullanılan Bearer tokenı iptal eder. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Çıkış yapıldı ve kullanılan token iptal edildi.',
        ]);
    }
}
