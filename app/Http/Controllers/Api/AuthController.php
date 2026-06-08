<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {

            $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {

                return response()->json([
                    'message' => 'Email atau password salah'
                ], 401);
            }

            $user = Auth::user();

            // Update last login aman
            try {
                $user->update([
                    'last_login_at' => now()
                ]);
            } catch (\Exception $e) {
                // abaikan jika kolom belum ada
            }

            // Buat token sanctum
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message'      => 'Login berhasil',
                'access_token' => $token,
                'token'        => $token,
                'user'         => $this->userResource($user),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'status' => 'ERROR',
                'error'  => $e->getMessage(),
                'file'   => $e->getFile(),
                'line'   => $e->getLine(),
            ], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userResource($request->user())
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    private function userResource(User $user): array
    {
        $lastLogin = 'Belum pernah login';

        if (!empty($user->last_login_at)) {

            try {
                $lastLogin = Carbon::parse(
                    $user->last_login_at
                )->diffForHumans();

            } catch (\Exception $e) {

                $lastLogin = (string) $user->last_login_at;
            }
        }

        return [

            'id' => $user->id ?? '-',

            'name' => $user->name ?? 'User',

            'email' => $user->email ?? '-',

            'phone' => $user->phone ?? '-',

            'department' => $user->department ?? '-',

            'avatar_url' => $user->avatar_url ?? null,

            // DISAFEKAN DULU
            'role' => 'Admin',

            'role_label' => 'Administrator',

            'is_active' => true,

            'last_login_at' => $lastLogin,
        ];
    }
}