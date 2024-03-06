<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;


class TokenRequest extends JsonFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|string|email',
            'password' => 'required',
        ];
    }
}
