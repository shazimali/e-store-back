<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use App\Http\Requests\TokenRequest;
use Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Models\User;

class AuthClass implements AuthInterface
{

    public function getToken($request)
    {
        // Verify reCAPTCHA
        if (!app()->environment('testing')) {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => env('RECAPTCHA_SECRET_KEY'),
                'response' => $request->recaptcha_token,
            ]);

            $googleResponse = $response->json();

            if (!($googleResponse['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'reCAPTCHA verification failed.',
                ], 422);
            }
        }

        if (!Auth::attempt($request->except(['recaptcha_token']))) {
            return response()->json([
                'message' => 'Invalid login details.'
            ], 401);
        }
        $user = auth()->user();
        $token = $user->createToken('auth_token')->plainTextToken;
        $permissions = [];
        foreach ($user->roles as $key => $role) {
            foreach ($role->permissions as $key => $permission) {
                array_push($permissions, $permission->key);
            }
        }
        $respon = [
            'status' => 'success',
            'msg' => 'Login successfully',
            'status_code' => 200,
            'permissions' => $permissions,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ];

        return response()->json($respon, 200);
    }

    public function logOut($id)
    {

        $user = User::find($id);
        if ($user) {
            $user->tokens()->delete();
            $respon = [
                'status' => 'success',
            ];
            return response()->json($respon, 200);
        }
        $respon = [
            'status' => 'Logout successfully.',
        ];
        return response()->json($respon, 200);
    }

    public function forgotPassword($data)
    {
        // Verify reCAPTCHA
        if (!app()->environment('testing')) {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => env('RECAPTCHA_SECRET_KEY'),
                'response' => $data->recaptcha_token,
            ]);

            $googleResponse = $response->json();

            if (!($googleResponse['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'reCAPTCHA verification failed.',
                ], 422);
            }
        }

        $status = Password::broker()->sendResetLink(
            $data->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 'success',
                'message' => 'We have emailed your password reset link.'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => __($status)
        ], 400);
    }

    public function resetPassword($data)
    {
        $status = Password::broker()->reset(
            $data->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'success',
                'message' => 'Your password has been reset successfully.'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => __($status)
        ], 400);
    }
}
