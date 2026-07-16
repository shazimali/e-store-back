<?php

namespace App\Services\Auth;

interface AuthInterface
{
    public function getToken($data);
    public function logOut($id);
    public function forgotPassword($data);
    public function resetPassword($data);
}