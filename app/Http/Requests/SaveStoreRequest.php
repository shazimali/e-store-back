<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;

class SaveStoreRequest extends JsonFormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'status' => 'required',
            'city_id' => 'required',
            'status' => 'required',
            'email' => 'nullable|email',
            'phone' => 'nullable|numeric',
            'code' => 'required|unique:stores,code',
        ];
    }

}
