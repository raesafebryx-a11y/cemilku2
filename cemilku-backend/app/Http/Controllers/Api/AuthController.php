<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // =====================================================
    // REGISTER
    // =====================================================

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        // =================================================
        // BUAT USER BARU
        // =================================================

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);

        // =================================================
        // BUAT TOKEN
        // =================================================

        $token = $user
            ->createToken('cemilku')
            ->plainTextToken;

        // =================================================
        // RESPONSE
        // =================================================

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // =====================================================
    // LOGIN
    // =====================================================

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        // =================================================
        // CARI USER
        // =================================================

        $user = User::where(
            'email',
            $data['email']
        )->first();

        // =================================================
        // CEK USER & PASSWORD
        // =================================================

        if (
            ! $user ||
            ! password_verify(
                $data['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'Email atau password salah.'
                ],
            ]);
        }

        // =================================================
        // BUAT TOKEN
        // =================================================

        $token = $user
            ->createToken('cemilku')
            ->plainTextToken;

        // =================================================
        // RESPONSE
        // =================================================

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    // =====================================================
    // LOGOUT
    // =====================================================

    public function logout(Request $request)
    {
        $request
            ->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Berhasil logout',
        ]);
    }

    // =====================================================
    // ME / USER YANG SEDANG LOGIN
    // =====================================================

    public function me(Request $request)
    {
        return response()->json(
            $request->user()
        );
    }

    // =====================================================
    // UPDATE PROFILE
    // =====================================================

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $user,
        ]);
    }

    // =====================================================
    // UPDATE PASSWORD
    // =====================================================

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user = $request->user();

        if (! password_verify($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'Password saat ini tidak cocok.'
                ],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah.',
        ]);
    }
}