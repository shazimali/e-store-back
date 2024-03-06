<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;

class StoreRoleRequest extends JsonFormRequest
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
            'name'     => [
                'required',
                'unique:roles,name',
                'max:50',
            ],
            'permissions'    => [
                'required',
                'array',
            ],
        ];   
    }
}
