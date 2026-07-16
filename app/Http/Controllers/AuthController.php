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

    public function token(TokenRequest $request)
    {
        return $this->authService->getToken($request);
    }

    public function logOut(Request $request)
    {
        return $this->authService->logOut($request->id);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        return $this->authService->forgotPassword($request);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->authService->resetPassword($request);
    }
}
