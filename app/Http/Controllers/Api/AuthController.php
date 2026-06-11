<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
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

    // 🌟 FUNGSI REGISTRASI: SEKARANG SINKRON DENGAN DATABASE LIVE RAILWAY
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role_id'  => 3, // 🌟 3 otomatis mendaftar sebagai Staff Gudang sesuai isi tabel roles kamu
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil! Silakan login.',
                'user'    => $this->userResource($user)
            ], 201);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registrasi gagal.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // 🌟 FUNGSI UPDATE INFORMASI PROFIL
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user'    => $this->userResource($user)
        ]);
    }

    // 🌟 FUNGSI UBAH PASSWORD AMAN
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min'       => 'Password baru minimal harus 8 karakter.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Password saat ini tidak sesuai.',
                'errors'  => ['current_password' => ['Password saat ini salah.']]
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah'
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
            'id'            => $user->id ?? '-',
            'name'          => $user->name ?? 'User',
            'email'         => $user->email ?? '-',
            'phone'         => $user->phone ?? '-',
            'department'    => $user->department ?? '-',
            'avatar_url'    => $user->avatar_url ?? null,
            'role'          => strtolower($user->role?->name ?? 'staff'), 
            'role_id'       => $user->role_id ?? 3,
            'role_label'    => $user->role?->display_name ?? 'Staff Gudang', // 🌟 Membaca kolom display_name dari database Railway kamu
            'is_active'     => true,
            'last_login_at' => $lastLogin,
        ];
    }
}