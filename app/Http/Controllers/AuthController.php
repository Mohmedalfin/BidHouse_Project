<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Requests\RegisterStoreRequest;
use App\Http\Requests\ProfileUpdateRequest;

use Exception;

class AuthController extends Controller
{
    /**
     * Login Users
     */
    public function login(Request $request)
    {
        try {
            // Coba autentikasi email dan password
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'data' => null
                ], 401);
            }

            // Jika berhasil, ambil user yang sedang login
            $user = Auth::user();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login Berhasil',
                'data' => [
                    'token' => $token,
                    'user' => new UserResource($user)
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register Users
     */
    public function register(RegisterStoreRequest $request)
    {
        $data = $request->validated();
        
        DB::beginTransaction();

        try {
            // Simpan user baru
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'Register berhasil bro',
                'data' => [
                    'token' => $token,
                    'user'  => new UserResource($user),
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store Users
     */
    public function show(){
        try{
            $user = Auth::user();

            // jaga-jaga kalau nggak lewat middleware
            if (!$user || !$user->currentAccessToken()) {
                return response()->json([
                    'message' => 'Tidak ada sesi login yang aktif',
                    'data' => null,
                ], 400);
            }

            return response()->json([
                'message' => 'Profile user berhasil diambil',
                'data' => new UserResource($user)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProfileUpdateRequest $request)
    {
        
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated',
                    'data'    => null,
                ], 401);
            }

            if (array_key_exists('name', $data)) {
                $user->name = $data['name'];
            }

            if (array_key_exists('email', $data)) {
                $user->email = $data['email'];
            }

            if (array_key_exists('password', $data)) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            DB::commit();

            return response()->json([
                'message' => 'Profile berhasil diperbarui',
                'data'    => new UserResource($user),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout Users
     */
    public function logout(){
        try{
            $user = Auth::user();

            // jaga-jaga kalau nggak lewat middleware
            if (!$user || !$user->currentAccessToken()) {
                return response()->json([
                    'message' => 'Tidak ada sesi login yang aktif',
                    'data' => null,
                ], 400);
            }

            $user->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout Berhasil',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi Kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
