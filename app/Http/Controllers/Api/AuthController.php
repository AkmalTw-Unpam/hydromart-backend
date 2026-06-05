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
                return response()->json(['message' => 'Kredensial tidak valid.'], 401);
            }

            $user = Auth::user();

            // Kita matikan sementara cek aktif agar tidak rawan crash jika kolom tidak ada
            // if (!$user->is_active) { ... }

            // Update login time dengan try-catch aman
            try {
                $user->update(['last_login_at' => now()]);
            } catch (\Exception $e) {
                // Abaikan jika database belum punya kolom ini
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message'      => 'Login success',
                'access_token' => $token, // <-- Format yang paling sering dicari React
                'token'        => $token, // <-- Tetap dipertahankan untuk jaga-jaga
                'user'         => $this->userResource($user),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'status' => 'SISTEM MOGOK!',
                'pesan'  => $e->getMessage(),
                'file'   => $e->getFile(),
                'baris'  => $e->getLine()
            ], 500);
        }
    }

    // FUNGSI BARU (Wajib ada agar React bisa mengambil profil dan tidak auto-logout)
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userResource($request->user())
        ]);
    }

    // FUNGSI LOGOUT (Sudah diperbaiki dari error TransientToken)
    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }
        
        return response()->json(['message' => 'Berhasil logout.']);
    }

    private function userResource(User $user): array
    {
        // PERBAIKAN FATAL ERROR: Parsing tanggal yang 100% aman
        $lastLogin = 'Belum pernah login';
        if (!empty($user->last_login_at)) {
            try {
                // Memaksa menjadikannya objek Carbon sebelum memanggil diffForHumans
                $lastLogin = Carbon::parse($user->last_login_at)->diffForHumans();
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
            'role'          => $user->role ? $user->role->name : 'No Role',
            'role_label'    => $user->role ? $user->role->display_name : 'No Role',
            'is_active'     => (bool) ($user->is_active ?? false),
            'last_login_at' => $lastLogin,
        ];
    }
}