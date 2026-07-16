<?php

namespace App\Http\Requests;

class ForgotPasswordRequest extends JsonFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|email|exists:users,email',
            'recaptcha_token' => app()->environment('testing') ? 'nullable' : 'required',
        ];
    }
}
