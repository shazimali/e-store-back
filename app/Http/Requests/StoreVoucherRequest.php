<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;

class StoreVoucherRequest extends JsonFormRequest
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
            'store_id' => 'required',
            'branch_id' => 'required',
            'amount' => 'required',
            'remarks' => 'required',
            'date' => 'required'
        ];
    }
}
