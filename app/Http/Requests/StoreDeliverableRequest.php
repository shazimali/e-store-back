<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonFormRequest;

class StoreDeliverableRequest extends JsonFormRequest
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
            'date'     => [
                'required'
            ],
            'store_id' => [
                'required'
            ],
            'branch_id' => [
                'required'
            ],
            'sr_number' => [
                'required',
                'unique:deliverables,sr_number'
            ],
            'products' => [
                'required',
                'array'
            ]
        ];
    }
}
