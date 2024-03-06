<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;


class StoreUserRequest extends JsonFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|between:8,255|confirmed',
            'roles' => 'required|array'
        ];
    }
}
