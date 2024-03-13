<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;

class StoreBranchRequest extends JsonFormRequest
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
            'store_id' => 'required',
            'code' => 'required|unique:branches,code',
        ];
    }
}
