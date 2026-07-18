<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TokenRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\Auth\AuthInterface;

class AuthController extends Controller
{
    public $authService;

    public function __construct(AuthInterface $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Post(
     *     path="/token",
     *     summary="Authenticate user and retrieve token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password", "recaptcha_token"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@everydaystore.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret"),
     *             @OA\Property(property="recaptcha_token", type="string", example="token-string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful authentication",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="1|AbCdEfGhIjKlMnOpQrStUvWxYz")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function token(TokenRequest $request)
    {
        return $this->authService->getToken($request);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Log Out",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="placeholder", type="string", example="value")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function logOut(Request $request)
    {
        return $this->authService->logOut($request->id);
    }

    /**
     * @OA\Post(
     *     path="/forgot-password",
     *     summary="Forgot Password",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="placeholder", type="string", example="value")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        return $this->authService->forgotPassword($request);
    }

    /**
     * @OA\Post(
     *     path="/reset-password",
     *     summary="Reset Password",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="placeholder", type="string", example="value")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->authService->resetPassword($request);
    }
}
