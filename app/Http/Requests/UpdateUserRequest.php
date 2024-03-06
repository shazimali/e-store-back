<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;

class UpdateUserRequest extends JsonFormRequest
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
            'email' => 'required',
            'roles' => 'required|array',
            'password' =>  'nullable|between:8,255|confirmed'
        ];
    }
}
