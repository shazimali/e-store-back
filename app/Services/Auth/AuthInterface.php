<?php
 namespace App\Services\Auth;

Interface AuthInterface{
    public function getToken($data);
    public function logOut($id);
 }
?>