<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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
            'password' => $data['password'],
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
}
