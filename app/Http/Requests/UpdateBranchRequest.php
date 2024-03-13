<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;

class UpdateBranchRequest extends JsonFormRequest
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
            'store_id' => 'required',
            'status' => 'required',
            'city_id' => 'required',
            'email' => 'nullable|email',
            'code' => 'required|unique:branches,code,'.$this->id,
            'phone' => 'nullable|numeric',
        ];
    }
}
